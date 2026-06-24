/* Postwave Admin JS — v3 */
(function () {
  'use strict';

  var cfg  = window.postwave || {};
  var i18n = cfg.i18n || {};

  function $(sel, ctx)  { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

  /* ════════════════════════════════
     TOGGLE SWITCH
     WP admin overrides :checked CSS.
     We drive state exclusively via JS class.
  ════════════════════════════════ */

  function initToggles() {
    $$('.pw-toggle').forEach(function (wrapper) {
      var cb    = wrapper.querySelector('.pw-toggle-cb');
      var track = wrapper.querySelector('.pw-toggle-track');
      if (!cb || !track) return;

      function syncState() {
        track.classList.toggle('is-on', cb.checked);
      }

      syncState(); // initial render
      cb.addEventListener('change', syncState);
      cb.addEventListener('focus', function () { track.classList.add('is-focused'); });
      cb.addEventListener('blur',  function () { track.classList.remove('is-focused'); });
    });
  }

  /* ════════════════════════════════
     PASSWORD EYE TOGGLE
  ════════════════════════════════ */

  function initPasswordToggles() {
    $$('.pw-eye-btn[data-for]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-for'));
        if (!input) return;
        var nowVisible = input.type === 'password';
        input.type = nowVisible ? 'text' : 'password';

        var eyeOn  = btn.querySelector('.pw-eye-on');
        var eyeOff = btn.querySelector('.pw-eye-off');
        if (eyeOn)  eyeOn.style.display  = nowVisible ? 'none' : '';
        if (eyeOff) eyeOff.style.display = nowVisible ? ''     : 'none';
      });
    });
  }

  /* ════════════════════════════════
     SETUP WIZARD
  ════════════════════════════════ */

  var currentStep = 0;

  function initWizard() {
    var startBtn = $('#pw-wz-start');
    var wBody    = $('#pw-wz-body');
    var ws0      = $('#pw-ws0');
    if (!startBtn) return;

    startBtn.addEventListener('click', function () {
      ws0.style.opacity = '0';
      ws0.style.transition = 'opacity .15s';
      setTimeout(function () {
        ws0.style.display = 'none';
        wBody.style.display = '';
        goStep(1);
      }, 160);
    });

    $$('[data-next]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var n = parseInt(btn.getAttribute('data-next'), 10);
        if (validateStep(currentStep)) goStep(n);
      });
    });

    $$('[data-prev]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        goStep(parseInt(btn.getAttribute('data-prev'), 10));
      });
    });
  }

  function goStep(n) {
    if (currentStep) {
      var cur = $('#pw-ws' + currentStep);
      if (cur) cur.style.display = 'none';
    }
    currentStep = n;
    var next = $('#pw-ws' + n);
    if (next) next.style.display = '';
    renderProgress(n);
  }

  function renderProgress(n) {
    $$('.pw-pdot').forEach(function (dot) {
      var s = parseInt(dot.getAttribute('data-n'), 10);
      dot.classList.remove('pw-pdot-active', 'pw-pdot-done');
      if      (s < n)  dot.classList.add('pw-pdot-done');
      else if (s === n) dot.classList.add('pw-pdot-active');
    });
    $$('.pw-pline').forEach(function (line, i) {
      line.classList.toggle('pw-pline-done', i + 1 < n);
    });
  }

  function validateStep(n) {
    var step = $('#pw-ws' + n);
    if (!step) return true;
    var valid = true;
    $$('input[required]', step).forEach(function (inp) {
      inp.style.borderColor = '';
      if (!inp.value.trim()) {
        inp.style.borderColor = '#d63638';
        if (valid) inp.focus();
        valid = false;
      }
    });
    return valid;
  }

  /* ════════════════════════════════
     CONNECTION / EMAIL TEST
  ════════════════════════════════ */

  function setStep(id, state) {
    var row = $('#pw-step-' + id);
    if (!row) return;
    var dot = row.querySelector('.pw-step-dot');
    row.className = 'pw-step-row' + (state ? ' pw-step-row--' + state : '');
    if (dot) {
      dot.textContent =
        state === 'done'    ? '✓' :
        state === 'error'   ? '✕' :
        state === 'loading' ? '◆' : '○';
    }
  }

  function showTestResult(ok, data) {
    var el = $('#pw-test-result');
    if (!el) return;
    el.style.display = '';
    el.className = 'pw-test-result pw-test-result--' + (ok ? 'ok' : 'err');
    el.innerHTML  = '';

    var title = document.createElement('strong');
    title.textContent = (data && data.message) ? data.message : (ok ? 'OK' : 'Error');
    el.appendChild(title);

    if (!ok || !data) return;

    if (data.account)   appendRow(el, i18n.account   || 'Account',      data.account,   true);
    if (data.identity)  appendRow(el, i18n.identity  || 'Identity',     data.identity,  false);
    if (data.recipient) appendRow(el, i18n.recipient || 'Recipient',    data.recipient, true);

    if (data.capabilities && data.capabilities.length) {
      var capRow = document.createElement('div');
      capRow.className = 'pw-result-row';
      var dt = document.createElement('dt');
      dt.textContent = (i18n.capabilities || 'Capabilities') + ' (' + data.capabilities.length + ')';
      capRow.appendChild(dt);
      el.appendChild(capRow);
      var ul = document.createElement('ul');
      ul.className = 'pw-caps';
      data.capabilities.forEach(function (c) {
        var li = document.createElement('li');
        var code = document.createElement('code');
        code.textContent = c;
        li.appendChild(code);
        ul.appendChild(li);
      });
      el.appendChild(ul);
    }

    if (data.warnings && data.warnings.length) {
      var w = document.createElement('div');
      w.className = 'pw-warns';
      var ws = document.createElement('strong');
      ws.textContent = i18n.warnings || 'Warnings';
      w.appendChild(ws);
      var wul = document.createElement('ul');
      data.warnings.forEach(function (msg) {
        var li = document.createElement('li');
        li.textContent = msg;
        wul.appendChild(li);
      });
      w.appendChild(wul);
      el.appendChild(w);
    }
  }

  function appendRow(parent, label, value, asCode) {
    var row = document.createElement('div');
    row.className = 'pw-result-row';
    var dt = document.createElement('dt');
    dt.textContent = label;
    var dd = document.createElement('dd');
    if (asCode) {
      var code = document.createElement('code');
      code.textContent = value;
      dd.appendChild(code);
    } else {
      dd.textContent = value;
    }
    row.appendChild(dt);
    row.appendChild(dd);
    parent.appendChild(row);
  }

  function runTest(type) {
    var stepsEl  = $('#pw-steps');
    var resultEl = $('#pw-test-result');
    if (stepsEl)  stepsEl.style.display = 'flex';
    if (resultEl) resultEl.style.display = 'none';

    setStep('discover', 'loading');
    setStep('identity', 'pending');
    setStep('done',     'pending');

    var btns = $$('#pw-test-conn, #pw-test-email');
    btns.forEach(function (b) { b.disabled = true; });

    var timer = setTimeout(function () {
      setStep('discover', 'done');
      setStep('identity', 'loading');
    }, 650);

    var fd = new FormData();
    fd.append('action', 'postwave_test');
    fd.append('nonce',  cfg.nonce || '');
    fd.append('type',   type);

    fetch(cfg.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        clearTimeout(timer);
        setStep('discover', 'done');
        setStep('identity', r.success ? 'done' : 'error');
        setStep('done',     r.success ? 'done' : 'error');
        showTestResult(r.success, r.data);
      })
      .catch(function (e) {
        clearTimeout(timer);
        ['discover', 'identity', 'done'].forEach(function (s) { setStep(s, 'error'); });
        showTestResult(false, { message: e.message || 'Network error' });
      })
      .finally(function () {
        btns.forEach(function (b) { b.disabled = false; });
      });
  }

  /* ════════════════════════════════
     LOG DETAIL ROWS
  ════════════════════════════════ */

  function initLogDetails() {
    $$('.pw-detail-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var panel = btn.nextElementSibling;
        if (!panel) return;
        var isOpen = panel.style.display === 'grid';
        panel.style.display = isOpen ? 'none' : 'grid';
        btn.textContent = isOpen ? 'Details ▾' : 'Details ▴';
      });
    });
  }

  /* ════════════════════════════════
     BOOT
  ════════════════════════════════ */

  /* ════════════════════════════════
     RETRY OPTIONS SHOW/HIDE
  ════════════════════════════════ */

  function initRetryToggle() {
    var cb      = document.querySelector( '[name="postwave[retry_enabled]"]:not([type="hidden"])' );
    var options = document.querySelector( '.pw-retry-options' );
    if ( ! cb || ! options ) { return; }

    function sync() {
      options.classList.toggle( 'pw-hidden', ! cb.checked );
    }
    sync();
    cb.addEventListener( 'change', sync );
  }

  /* ════════════════════════════════
     LOAD JMAP IDENTITIES
  ════════════════════════════════ */

  function initIdentityLoader() {
    var btn    = document.getElementById( 'pw-load-identities' );
    var select = document.getElementById( 'pw-identity-select' );
    var status = document.getElementById( 'pw-identity-status' );
    var hidName  = document.getElementById( 'pw-identity-name' );
    var hidEmail = document.getElementById( 'pw-identity-email' );
    if ( ! btn || ! select ) { return; }

    btn.addEventListener( 'click', function () {
      btn.disabled    = true;
      btn.textContent = 'Loading…';
      if ( status ) { status.textContent = ''; status.classList.add( 'pw-hidden' ); }

      var fd = new FormData();
      fd.append( 'action', 'postwave_fetch_identities' );
      fd.append( 'nonce',  cfg.identities_nonce || '' );

      fetch( cfg.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' } )
        .then( function( r ) { return r.json(); } )
        .then( function( data ) {
          btn.disabled    = false;
          btn.textContent = 'Load identities';

          if ( ! data.success ) {
            if ( status ) { status.textContent = data.data.message; status.classList.remove( 'pw-hidden' ); }
            return;
          }

          var identities = data.data.identities || [];
          var saved      = select.value;

          // Rebuild options (keep the auto-resolve first option).
          while ( select.options.length > 1 ) { select.remove( 1 ); }

          identities.forEach( function( id ) {
            var opt   = document.createElement( 'option' );
            opt.value = id.id;
            opt.text  = id.name ? id.name + ' <' + id.email + '>' : id.email;
            if ( id.id === saved ) { opt.selected = true; }
            select.appendChild( opt );
          } );

          // Update hidden name/email fields when selection changes.
          function syncHidden() {
            var idx   = select.selectedIndex;
            var selId = idx >= 0 ? select.options[ idx ].value : '';
            identities.forEach( function( id ) {
              if ( id.id === selId ) {
                if ( hidName )  { hidName.value  = id.name  || ''; }
                if ( hidEmail ) { hidEmail.value = id.email || ''; }
              }
            } );
            if ( ! selId ) {
              if ( hidName )  { hidName.value  = ''; }
              if ( hidEmail ) { hidEmail.value = ''; }
            }
          }
          select.addEventListener( 'change', syncHidden );
          syncHidden();

          if ( status ) {
            status.textContent = identities.length + ' identit' + ( identities.length === 1 ? 'y' : 'ies' ) + ' loaded.';
            status.classList.remove( 'pw-hidden' );
          }
        } )
        .catch( function() {
          btn.disabled    = false;
          btn.textContent = 'Load identities';
          if ( status ) { status.textContent = 'Request failed. Try again.'; status.classList.remove( 'pw-hidden' ); }
        } );
    } );
  }

  /* ════════════════════════════════
     BOOT
  ════════════════════════════════ */

  document.addEventListener('DOMContentLoaded', function () {
    initToggles();
    initPasswordToggles();
    initWizard();
    initLogDetails();
    initRetryToggle();
    initIdentityLoader();

    var connBtn  = $('#pw-test-conn');
    var emailBtn = $('#pw-test-email');
    if (connBtn)  connBtn.addEventListener('click',  function () { runTest('connection'); });
    if (emailBtn) emailBtn.addEventListener('click', function () { runTest('email'); });

    initAccountManager();
    initRoutingUI();
  });

  // ── v1.2: Account manager UI ─────────────────────────────────────────────

  function initAccountManager() {
    var addBtn    = document.getElementById( 'pw-add-account-btn' );
    var form      = document.getElementById( 'pw-account-form' );
    var cancelBtn = document.getElementById( 'pw-account-form-cancel' );
    var idField   = document.getElementById( 'pw-account-id' );
    var formTitle = document.getElementById( 'pw-account-form-title' );
    if ( ! addBtn || ! form ) return;

    addBtn.addEventListener( 'click', function () {
      // Reset form to "add new" state.
      if ( idField )   idField.value = '';
      if ( formTitle ) formTitle.textContent = 'Add account';
      // Clear all inputs.
      form.querySelectorAll( 'input[type=text], input[type=url], input[type=password]' ).forEach( function ( el ) {
        el.value = '';
      } );
      form.classList.remove( 'pw-hidden' );
      form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
    } );

    if ( cancelBtn ) {
      cancelBtn.addEventListener( 'click', function () {
        form.classList.add( 'pw-hidden' );
      } );
    }

    // Edit buttons on account cards.
    document.querySelectorAll( '.pw-edit-account-btn' ).forEach( function ( btn ) {
      btn.addEventListener( 'click', function () {
        var card = btn.closest( '.pw-account-card' );
        if ( ! card ) return;
        var data = JSON.parse( card.dataset.account || '{}' );

        if ( idField )   idField.value = data.id || '';
        if ( formTitle ) formTitle.textContent = 'Edit account';

        var fields = {
          'pw-account-name':       data.name           || '',
          'pw-account-server-url': data.server_url     || '',
          'pw-account-username':   data.username       || '',
          'pw-account-iname':      data.identity_name  || '',
          'pw-account-iemail':     data.identity_email || '',
          'pw-account-iid':        data.identity_id    || '',
        };
        Object.keys( fields ).forEach( function ( id ) {
          var el = document.getElementById( id );
          if ( el ) el.value = fields[ id ];
        } );

        // Always clear password when editing.
        var pwEl = document.getElementById( 'pw-account-password' );
        if ( pwEl ) pwEl.value = '';

        form.classList.remove( 'pw-hidden' );
        form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
      } );
    } );

    // Test account button (AJAX).
    var testBtn    = document.getElementById( 'pw-test-account-btn' );
    var testResult = document.getElementById( 'pw-test-account-result' );
    if ( testBtn ) {
      testBtn.addEventListener( 'click', function () {
        var accountId = ( idField && idField.value ) ? idField.value : 'acc_primary';
        testBtn.disabled    = true;
        testBtn.textContent = 'Testing…';
        if ( testResult ) testResult.textContent = '';

        var fd = new FormData();
        fd.append( 'action',     'postwave_test_account' );
        fd.append( 'nonce',      cfg.test_account_nonce || '' );
        fd.append( 'account_id', accountId );

        fetch( cfg.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' } )
          .then( function ( r ) { return r.json(); } )
          .then( function ( data ) {
            testBtn.disabled    = false;
            testBtn.textContent = 'Test connection';
            if ( testResult ) {
              testResult.textContent = data.success
                ? '✓ ' + ( data.data.message || 'OK' )
                : '✗ ' + ( ( data.data && data.data.message ) || 'Failed' );
              testResult.style.color = data.success ? '#00a32a' : '#d63638';
            }
          } )
          .catch( function () {
            testBtn.disabled    = false;
            testBtn.textContent = 'Test connection';
            if ( testResult ) { testResult.textContent = '✗ Request failed'; testResult.style.color = '#d63638'; }
          } );
      } );
    }
  }

  // ── v1.2: Routing rules UI ────────────────────────────────────────────────

  function initRoutingUI() {
    var addBtn      = document.getElementById( 'pw-add-rule-btn' );
    var ruleForm    = document.getElementById( 'pw-rule-form' );
    var cancelBtn   = document.getElementById( 'pw-rule-form-cancel' );
    var ruleIdField = document.getElementById( 'pw-rule-id' );
    var ruleTitle   = document.getElementById( 'pw-rule-form-title' );

    if ( addBtn && ruleForm ) {
      addBtn.addEventListener( 'click', function () {
        if ( ruleIdField ) ruleIdField.value = '';
        if ( ruleTitle )   ruleTitle.textContent = 'Add rule';
        // Reset form fields.
        ruleForm.querySelectorAll( 'input[type=text], input[type=checkbox]' ).forEach( function ( el ) {
          if ( el.type === 'checkbox' ) el.checked = true;
          else el.value = '';
        } );
        // Clear conditions (keep one empty row).
        var list = document.getElementById( 'pw-conditions-list' );
        if ( list ) {
          list.innerHTML = '';
          addConditionRow( list );
        }
        ruleForm.classList.remove( 'pw-hidden' );
        ruleForm.scrollIntoView( { behavior: 'smooth', block: 'start' } );
      } );
    }

    if ( cancelBtn && ruleForm ) {
      cancelBtn.addEventListener( 'click', function () {
        ruleForm.classList.add( 'pw-hidden' );
      } );
    }

    // Add condition row button.
    var addConditionBtn = document.getElementById( 'pw-add-condition-btn' );
    var conditionsList  = document.getElementById( 'pw-conditions-list' );
    if ( addConditionBtn && conditionsList ) {
      addConditionBtn.addEventListener( 'click', function () {
        addConditionRow( conditionsList );
      } );
    }

    // Edit rule buttons.
    document.querySelectorAll( '.pw-edit-rule-btn' ).forEach( function ( btn ) {
      btn.addEventListener( 'click', function () {
        var data = JSON.parse( btn.dataset.rule || '{}' );
        if ( ruleIdField ) ruleIdField.value = data.id || '';
        if ( ruleTitle )   ruleTitle.textContent = 'Edit rule';

        var nameEl = document.getElementById( 'pw-rule-name' );
        if ( nameEl ) nameEl.value = data.name || '';

        var enabledEl = document.getElementById( 'pw-rule-enabled' );
        if ( enabledEl ) enabledEl.checked = !! data.enabled;

        var opAny = document.querySelector( '[name="pw_rule[condition_operator]"][value="any"]' );
        var opAll = document.querySelector( '[name="pw_rule[condition_operator]"][value="all"]' );
        if ( opAny ) opAny.checked = ( data.condition_operator !== 'all' );
        if ( opAll ) opAll.checked = ( data.condition_operator === 'all' );

        var accountSel = document.getElementById( 'pw-rule-account' );
        if ( accountSel ) accountSel.value = data.account_id || 'acc_primary';

        var identityEl = document.getElementById( 'pw-rule-identity' );
        if ( identityEl ) identityEl.value = data.identity_id || '';

        // Populate conditions.
        var list = document.getElementById( 'pw-conditions-list' );
        if ( list ) {
          list.innerHTML = '';
          var conditions = data.conditions || [];
          if ( conditions.length === 0 ) {
            addConditionRow( list );
          } else {
            conditions.forEach( function ( c ) {
              addConditionRow( list, c.field, c.value );
            } );
          }
        }

        if ( ruleForm ) {
          ruleForm.classList.remove( 'pw-hidden' );
          ruleForm.scrollIntoView( { behavior: 'smooth', block: 'start' } );
        }
      } );
    } );
  }

  function addConditionRow( list, field, value ) {
    var row = document.createElement( 'div' );
    row.className = 'pw-condition-row';

    var fieldOpts = [
      { value: 'to_domain',        label: 'Recipient domain (e.g. example.com)' },
      { value: 'to_email',         label: 'Recipient email (exact)' },
      { value: 'from_email',       label: 'Sender email (exact)' },
      { value: 'subject_contains', label: 'Subject contains' },
      { value: 'plugin',           label: 'Plugin / email type' },
    ];

    var sel = document.createElement( 'select' );
    sel.name = 'pw_rule[condition_field][]';
    sel.innerHTML = '<option value="">— Select field —</option>';
    fieldOpts.forEach( function ( opt ) {
      var o = document.createElement( 'option' );
      o.value = opt.value;
      o.text  = opt.label;
      if ( opt.value === field ) o.selected = true;
      sel.appendChild( o );
    } );

    var inp = document.createElement( 'input' );
    inp.type        = 'text';
    inp.name        = 'pw_rule[condition_value][]';
    inp.value       = value || '';
    inp.placeholder = 'Value';

    var rm = document.createElement( 'button' );
    rm.type      = 'button';
    rm.className = 'pw-remove-condition';
    rm.textContent = '×';
    rm.title     = 'Remove';
    rm.addEventListener( 'click', function () {
      row.remove();
    } );

    row.appendChild( sel );
    row.appendChild( inp );
    row.appendChild( rm );
    list.appendChild( row );
  }

  // ── Toast system ─────────────────────────────────────────────
  function pwToast( message, type ) {
    type = type || 'success';
    var container = document.getElementById( 'pw-toast' );
    if ( ! container ) return;
    var item = document.createElement( 'div' );
    item.className = 'pw-toast__item pw-toast__item--' + type;
    item.textContent = message;
    container.appendChild( item );
    setTimeout( function() { item.classList.add( 'pw-toast__item--show' ); }, 20 );
    setTimeout( function() {
      item.classList.remove( 'pw-toast__item--show' );
      setTimeout( function() { item.parentNode && item.parentNode.removeChild( item ); }, 300 );
    }, 3500 );
  }
  window.pwToast = pwToast;

  // ── Wizard step navigation ────────────────────────────────────
  function initWizardOverlay() {
    var overlay = document.getElementById( 'pw-wizard' );
    if ( ! overlay ) return;

    var currentStep = 0;
    var steps = overlay.querySelectorAll( '.pw-wizard__step' );
    var dots  = overlay.querySelectorAll( '.pw-wizard__step-dot' );

    function showStep( idx ) {
      steps.forEach( function( s, i ) {
        s.classList.toggle( 'pw-wizard__step--active', i === idx );
      } );
      dots.forEach( function( d, i ) {
        d.classList.toggle( 'pw-wizard__step-dot--active', i === idx );
      } );
      currentStep = idx;
    }

    // Next buttons
    overlay.querySelectorAll( '[data-wizard-next]' ).forEach( function( btn ) {
      btn.addEventListener( 'click', function() {
        var next = parseInt( btn.getAttribute( 'data-wizard-next' ), 10 );
        showStep( next );
      } );
    } );

    // Skip link
    var skip = overlay.querySelector( '[data-wizard-skip]' );
    if ( skip ) {
      skip.addEventListener( 'click', function(e) {
        e.preventDefault();
        overlay.classList.remove( 'pw-wizard--active' );
      } );
    }

    // Finish button
    var finish = overlay.querySelector( '[data-wizard-finish]' );
    if ( finish ) {
      finish.addEventListener( 'click', function() {
        overlay.classList.remove( 'pw-wizard--active' );
      } );
    }

    showStep( 0 );
  }

  // ── Relative time ─────────────────────────────────────────────
  function initRelativeTime() {
    document.querySelectorAll( '[data-timestamp]' ).forEach( function( el ) {
      var ts = parseInt( el.getAttribute( 'data-timestamp' ), 10 );
      if ( ! ts ) return;
      var now  = Math.floor( Date.now() / 1000 );
      var diff = now - ts;
      var label;
      if ( diff < 60 )        { label = 'just now'; }
      else if ( diff < 3600 ) { label = Math.floor( diff / 60 ) + 'm ago'; }
      else if ( diff < 86400 ){ label = Math.floor( diff / 3600 ) + 'h ago'; }
      else                    { label = Math.floor( diff / 86400 ) + 'd ago'; }
      el.textContent = label;
      el.title = el.getAttribute( 'data-date' ) || '';
    } );
  }

  document.addEventListener( 'DOMContentLoaded', function() {
    initWizardOverlay();
    initRelativeTime();

    // Show toast for WP admin notices (settings saved)
    var url = window.location.href;
    if ( url.indexOf( 'settings-updated=1' ) !== -1 || url.indexOf( 'saved=1' ) !== -1 ) {
      pwToast( 'Settings saved.' );
    }
  } );

})();
