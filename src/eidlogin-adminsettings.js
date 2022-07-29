//** js for the adminsettings of the eidlogin nextcloud app */
import { generateUrl } from '@nextcloud/router'
import '@nextcloud/dialogs/styles/toast.scss'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { getRequestToken } from '@nextcloud/auth'

// an improved debounce function from http://modernjavascript.blogspot.com/2013/08/building-better-debounce.html
var debounce = function(func, wait) {
  var timeout, args, context, timestamp;
  return function() {
    context = this;
    args = [].slice.call(arguments, 0);
    timestamp = Date.now();
    var later = function() {
      var last = (Date.now()) - timestamp;
      if (last < wait) {
        timeout = setTimeout(later, wait - last);
      } else {
        timeout = null;
        func.apply(context, args);
      }
    };
    if (!timeout) {
      timeout = setTimeout(later, wait);
    }
  }
};

document.addEventListener('DOMContentLoaded', function(e) {

  // dom elements
  const dataSrc = document.getElementById('eidlogin-settings-datasrc');
  const wizard = document.getElementById('eidlogin-settings-wizard');
  const manual = document.getElementById('eidlogin-settings-manual');
  const buttonHelp = document.getElementById('eidlogin-settings-button-help');
  const buttonSelectSkid = document.getElementById('eidlogin-settings-button-select-skid');
  const inputMetaIdp = document.getElementById('eidlogin-settings-form-wizard-idp_metadata_url');
  const buttonToggleIdp = document.getElementById('eidlogin-settings-button-toggleidp');
  const skidCell1 = document.getElementById('eidlogin-settings-skid-cell-1');
  const skidCell2 = document.getElementById('eidlogin-settings-skid-cell-2');
  const buttonToggleSp = document.getElementById('eidlogin-settings-button-togglesp');
  const buttonWizardSave = document.getElementById('eidlogin-settings-button-next-3');
  const stepWizardSave = document.getElementById('eidlogin-settings-wizard-step-3');
  const stepWizardActivate = document.getElementById('eidlogin-settings-wizard-step-4');
  const buttonWizardActivate = document.getElementById('eidlogin-settings-button-next-4');
  const buttonWizardFinish = document.getElementById('eidlogin-settings-button-finish');
  const certActDiv = document.getElementById('eidlogin-settings-manual-div-cert-act');
  const certActEncDiv = document.getElementById('eidlogin-settings-manual-div-cert-act-enc');
  const certNewDiv = document.getElementById('eidlogin-settings-manual-div-cert-new');
  const certNewEncDiv = document.getElementById('eidlogin-settings-manual-div-cert-new-enc');
  const buttonRolloverPrep = document.getElementById('eidlogin-settings-button-rollover-prepare');
  const buttonRolloverExec = document.getElementById('eidlogin-settings-button-rollover-execute');
  const spanRolloverExec = document.getElementById('eidlogin-settings-span-rollover-execute');

  // global const and vars
  const skidMetadataUrl = 'https://service.skidentity.de/fs/saml/metadata';
  const skidManagementUrl = 'https://sp.skidentity.de/';
  const requesttoken = getRequestToken();
  const txtShowIdp = t('eidlogin','Advanced Settings');
  const txtHideIdp = t('eidlogin','Hide Advanced Settings');
  const txtShowSp = t('eidlogin','Show Service Provider Metadata');
  const txtHideSp = t('eidlogin','Hide Service Provider Metadata');
  var activated = false;

  // helpers for the wizard
  //
  // select skid and save instantly
  function selectSkid(e) {
    document.getElementById('eidlogin-settings-form-wizard-idp_metadata_url').value = skidMetadataUrl;
    updateIdpSettings(e);
  }
  buttonSelectSkid.addEventListener('click', selectSkid);

  // switch the active wizard panel and reconfigure step links
  function switchWizardPanel(panel) {
    panel = parseInt(panel);
    buttonToggleIdp.innerText=txtShowIdp;
    buttonToggleSp.innerText=txtShowSp;
    wizard.getElementsByClassName('step').forEach(el => {
      el.classList.remove('active');
      el.classList.add('disabled');
      el.removeEventListener('click', switchPanelEventListener);
      el.removeEventListener('click', saveSettings);
    });
    wizard.getElementsByClassName('panel').forEach(el => {
      el.classList.remove('active');el.classList.add('hidden')
    });
    document.getElementById('eidlogin-settings-wizard-panel-'+panel).classList.remove('hidden');
    for (var i=1;i<=parseInt(panel)+1;i++) {
      // enable panel switching via step links
      if (i<=4) {
        // enable form save via step link 3 coming from the start
        if (panel<=2 && i==3) {
          document.getElementById('eidlogin-settings-wizard-step-'+i).addEventListener('click', saveSettings);
        } else {
          document.getElementById('eidlogin-settings-wizard-step-'+i).addEventListener('click', switchPanelEventListener);
        }
        document.getElementById('eidlogin-settings-wizard-step-'+i).classList.remove('disabled');
      }
    }
    document.getElementById('eidlogin-settings-wizard-step-'+panel).classList.add('active');
  }

  // toggle the wizard help div
  function toggleHelp() {
    const panelHelp = document.getElementById('eidlogin-settings-wizard-panel-help');
    if (panelHelp.classList.contains('hidden')) {
      panelHelp.classList.remove('hidden');
      buttonHelp.classList.add('active');
    } else {
      panelHelp.classList.add('hidden');
      buttonHelp.classList.remove('active');
    }
  }
  document.querySelectorAll('[data-help="help"]').forEach(el=>el.addEventListener('click', toggleHelp));

  // switch the active wizard panel by buttons
  function switchPanelEventListener(e) {
    e.preventDefault();
    // don`t switch if we use skid, save or activate, is handled in saveSettings and activate
    if (e.target === buttonSelectSkid || e.target === buttonWizardSave || e.target === buttonWizardActivate || e.target === stepWizardActivate) {
      return;
    }
    switchWizardPanel(e.target.dataset.panel);
  }
  document.querySelectorAll('button[data-panel]').forEach(el=>el.addEventListener('click', switchPanelEventListener));

  // fetch and replace idp metadata values when url is changed
  function updateIdpSettings(e) {
    const errMsg = t('eidlogin','Identity Provider settings could not be fetched');
    const sp_enforce_enc = document.getElementById('eidlogin-settings-form-wizard-sp_enforce_enc');
    const idp_cert_enc = document.getElementById('eidlogin-settings-form-wizard-idp_cert_enc');
    const idp_cert_sign = document.getElementById('eidlogin-settings-form-wizard-idp_cert_sign');
    const idp_entity_id = document.getElementById('eidlogin-settings-form-wizard-idp_entity_id');
    const idp_sso_url = document.getElementById('eidlogin-settings-form-wizard-idp_sso_url');
    const idp_ext_tr03130 = document.getElementById('eidlogin-settings-form-wizard-idp_ext_tr03130');
    if (inputMetaIdp.value==='') {
      sp_enforce_enc.value = '';
      idp_cert_enc.value = '';
      idp_cert_sign.value = '';
      idp_entity_id.value = '';
      idp_sso_url.value = '';
      idp_ext_tr03130.value = '';

      return;
    }
    buttonWizardSave.disabled = true;
    stepWizardSave.classList.add("disabled");
    var idpMetaURL = inputMetaIdp.value;
    idpMetaURL = encodeURIComponent(idpMetaURL);
    idpMetaURL = btoa(idpMetaURL);
    var url = generateUrl('/apps/eidlogin/settings/fetchidp');
    url += "/"+idpMetaURL;
    var xhr = new XMLHttpRequest();
    xhr.addEventListener('load', (e2)=>{
      sp_enforce_enc.value = '';
      idp_cert_enc.value = '';
      idp_cert_sign.value = '';
      idp_entity_id.value = '';
      idp_sso_url.value = '';
      idp_ext_tr03130.value = '';
      if(e2.target.status == 200) {
        const idpMetadata = JSON.parse(e2.target.responseText);
        idp_cert_enc.value = idpMetadata['idp_cert_enc'];
        idp_cert_sign.value = idpMetadata['idp_cert_sign'];
        idp_entity_id.value = idpMetadata['idp_entity_id'];
        idp_sso_url.value = idpMetadata['idp_sso_url'];
        if (e.target==buttonSelectSkid) {
          saveSettings(e);
        }
      } else if(e2.target.status == 500) {
        showError(errMsg);
      }
      buttonWizardSave.disabled = false;
      stepWizardSave.classList.remove("disabled");
    });
    xhr.addEventListener('error', (e2)=>{
        showError(errMsg);
    });
    xhr.open('GET', url, true);
    xhr.setRequestHeader('requesttoken', requesttoken);
    xhr.send();
  }
  inputMetaIdp.addEventListener('input', debounce(updateIdpSettings, 200));

  // toggle idp settings under configure panel
  function toggleIdp(e) {
    e.preventDefault();
    const panelIdpSettings = document.getElementById('eidlogin-settings-wizard-panel-idp_settings');
    if (panelIdpSettings.classList.contains('hidden')) {
      panelIdpSettings.classList.remove('hidden');
      buttonToggleIdp.innerText=txtHideIdp;
    } else {
      panelIdpSettings.classList.add('hidden');
      buttonToggleIdp.innerText=txtShowIdp;
    }
  }
  buttonToggleIdp.addEventListener('click', toggleIdp);

  // save the settings with a post of the form to SettingsController
  function saveSettings(e) {
    const errMsg = t('eidlogin','Settings could not be saved');
    // maybe we need to switch panel
    const switchPanel = e.target.dataset.panel=="3";
    const url = generateUrl('/apps/eidlogin/settings/save');
    var form;
    if (wizard.classList.contains('hidden')) {
      form = document.getElementById('eidlogin-settings-form-manual');
    } else {
      form = document.getElementById('eidlogin-settings-form-wizard');
    }
    const formData = new FormData(form);
    var xhr = new XMLHttpRequest();
    xhr.addEventListener('load', (e)=>{
      if(e.target.status == 200) {
        var msg = JSON.parse(e.target.responseText).message;
        showSuccess(msg);
        // maybe we need to switch panel
        if (switchPanel) {
          // display the sp_entity_id
          document.getElementById('eidlogin-settings-wizard-display-sp_entity_id').innerText=document.getElementById('eidlogin-settings-form-wizard-sp_entity_id').value;
          // hide the skid button and it`s text, if we don't have skid as configured idp
          if (inputMetaIdp.value===skidMetadataUrl) {
            skidCell1.classList.remove('hidden');
            skidCell2.classList.remove('hidden');
          } else {
            skidCell1.classList.add('hidden');
            skidCell2.classList.add('hidden');
          }
          switchWizardPanel(3);
        }
      } else if(e.target.status == 500) {
        showError(errMsg);
      } else {
        var resp = JSON.parse(e.target.responseText);
        resp.errors.forEach(error => {
          showError(error);
        });
      }
    });
    xhr.addEventListener('error', (e)=>{
        showError(errMsg);
    });
    xhr.open('POST', url, true);
    xhr.setRequestHeader('requesttoken', requesttoken);
    xhr.send(formData);
  }
  buttonWizardSave.addEventListener('click', saveSettings);

  // open skid in a new tab/win
  function openSkid(e) {
    e.preventDefault();
    window.open(skidManagementUrl,'_blank');
  }
  document.getElementById('eidlogin-settings-button-skid').addEventListener('click', openSkid);

  // activate the eID-Login after security question
  function activate(e) {
    if (activated) {
      switchWizardPanel(4);
      return
    }
    OC.dialogs.confirmDestructive(
      t('eidlogin',"Please confirm that the Service Provider has been registered at the Identity Provider. Pressing the 'Next' button will activate the eID-Login."),
      t('eidlogin','Activate eID-Login'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Next'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
        if (result) {
          const errMsg = t('eidlogin','eID-Login could not be activated');
          const successMsg = t('eidlogin','eID-Login is activated');
          const url = generateUrl('/apps/eidlogin/settings/toggleactivated');
          var xhr = new XMLHttpRequest();
          xhr.addEventListener('load', (e)=>{
            if(e.target.status == 200) {
              activated = true;
              showSuccess(successMsg);
              switchWizardPanel(4);
            } else {
              showError(errMsg);
            }
          });
          xhr.addEventListener('error', (e)=>{
              showError(errMsg);
          });
          xhr.open('GET', url, true);
          xhr.setRequestHeader('requesttoken', requesttoken);
          xhr.send();
        }
      },
      true
    );
  }
  buttonWizardActivate.addEventListener('click', activate);
  stepWizardActivate.addEventListener('click', activate);

  // finish the wizard with a page reload
  function finish(e) {
    window.scrollTo(0,0);
    window.location.reload();
  }
  buttonWizardFinish.addEventListener('click', finish);

  // toggle sp metadata under idp panel
  function toggleSp(e) {
    e.preventDefault();
    const spPanel = document.getElementById('eidlogin-settings-wizard-panel-register-sp');
    if (spPanel.classList.contains('hidden')) {
      const errMsg = t('eidlogin','Service Provider metadata could not be fetched');
      const url = generateUrl('/apps/eidlogin/saml/meta');
      var xhr = new XMLHttpRequest();
      xhr.addEventListener('load', (e)=>{
        if(e.target.status == 200) {
          var spMetadata = e.target.responseText;
          var spMetadataPre = document.getElementById('eidlogin-settings-wizard-panel-register-sp-metadata');
          spMetadataPre.innerText = "";
          spMetadataPre.appendChild(document.createTextNode(spMetadata));
        } else {
          showError(errMsg);
        }
      });
      xhr.addEventListener('error', (e)=>{
          showError(errMsg);
      });
      xhr.open('GET', url, true);
      xhr.setRequestHeader('requesttoken', requesttoken);
      xhr.send();
      buttonToggleSp.innerText=txtHideSp;
      spPanel.classList.remove('hidden');
    } else {
      buttonToggleSp.innerText=txtShowSp;
      spPanel.classList.add('hidden');
    }
  }
  buttonToggleSp.addEventListener('click', toggleSp);

  // createEid
  function createEid() {
    var requesttoken = getRequestToken();
    var url = generateUrl('/apps/eidlogin/eid/createeid');
    url += '?requesttoken='+encodeURIComponent(requesttoken)
    window.location.href=url;
  }
  document.getElementById('eidlogin-settings-button-eid-create').addEventListener('click', createEid);

  // toggle activated state of the app
  function toggleActivated() {
    const errMsg = t('eidlogin','eID-Login activated state could not be changed');
    const url = generateUrl('/apps/eidlogin/settings/toggleactivated');
    var xhr = new XMLHttpRequest();
    xhr.addEventListener('load', (e)=>{
      if(e.target.status == 200) {
        var msg = JSON.parse(e.target.responseText).message;
        showSuccess(msg);
      } else {
        showError(errMsg);
      }
    });
    xhr.addEventListener('error', (e)=>{
        showError(errMsg);
    });
    xhr.open('GET', url, true);
    xhr.setRequestHeader('requesttoken', requesttoken);
    xhr.send();
  }
  document.getElementById('eidlogin-settings-label-activated').addEventListener('click', toggleActivated);

  // save the settings after checking about the deletion of existing eids
  function confirmSave(e) {
    e.preventDefault();
    OC.dialogs.confirmDestructive(
      t('eidlogin','Changing the Identity Provider Settings will very likely make existing eID connections not work anymore, as they are bound to a specific Identity Provider! You maybe should make a backup of the settings before saving! Are you sure you want to save now?'),
      t('eidlogin','Save Settings'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Yes'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
        if (result) {
          OC.dialogs.confirmDestructive(
            t('eidlogin','Should all existing eID connections be deleted?'),
            t('eidlogin','Delete existing eID connections'),
            {
              type: OC.dialogs.YES_NO_BUTTONS,
              confirm: t('eidlogin', 'Yes'),
              confirmClasses: 'error',
              cancel: t('eidlogin', 'No')
            },
            (result) => {
              const inputEidDelete = document.getElementById('eidlogin-settings-form-manual-eid_delete');
              if (result) {
                inputEidDelete.value = 'true';
              } else {
                inputEidDelete.value = 'false';
              }
              saveSettings(e);
            }
          );
        }
      },
      true
    );
  }
  document.getElementById('eidlogin-settings-button-manual-save').addEventListener('click', confirmSave);

  // reset the settings with a post of the form to SettingsController
  function resetSettings(e) {
    e.preventDefault();
    const errMsg = t('eidlogin','Settings could not be reset');
    OC.dialogs.confirmDestructive(
      t('eidlogin','Reset of settings will also delete eID connections of all accounts. After this no account will be able to use the eID-Login anymore and all users must create a new eID connection! Are you sure?'),
      t('eidlogin','Reset Settings'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Yes'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
        if (result) {
          const url = generateUrl('/apps/eidlogin/settings/reset');
          var xhr = new XMLHttpRequest();
          xhr.addEventListener('load', (e)=>{
            if(e.target.status == 200) {
              var msg = JSON.parse(e.target.responseText).message;
              showSuccess(msg);
              window.location.reload();
            } else {
              showError(errMsg);
            }
          });
          xhr.addEventListener('error', (e)=>{
              showError(errMsg);
          });
          xhr.open('GET', url, true);
          xhr.setRequestHeader('requesttoken', requesttoken);
          xhr.send();
        }
      },
      true
    );
  }
  document.getElementById('eidlogin-settings-button-reset').addEventListener('click', resetSettings);

  // prepare a SAML Certificate Rollover
  function prepRollover(e) {
    e.preventDefault();
    const errMsg = t('eidlogin','Certificate Rollover could not be prepared')
    var msg = t('eidlogin','This will create new certificates which will be propagated in the Service Provider SAML Metadata. Are you sure?')
    if (certNewPresent==='true') {
      msg = t('eidlogin','This will replace the already prepared certificates and replace them with a new ones which will be propagated in the Service Provider SAML Metadata. Are you sure?')
    }
    OC.dialogs.confirmDestructive(
      msg,
      t('eidlogin','Prepare Certificate Rollover'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Yes'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
        if (result) {
          const url = generateUrl('/apps/eidlogin/settings/preparerollover');
          var xhr = new XMLHttpRequest();
          xhr.addEventListener('load', (e)=>{
            var msg = JSON.parse(e.target.responseText).message;
            var newCert = JSON.parse(e.target.responseText).cert_new;
            var newCertEnc = JSON.parse(e.target.responseText).cert_new_enc;
            if(e.target.status == 200) {
              certNewDiv.innerText = '... '+newCert;
              certNewEncDiv.innerText = '... '+newCertEnc;
              buttonRolloverExec.disabled = false;
              spanRolloverExec.classList.add('hidden');
              showSuccess(msg);
            } else {
              showError(msg);
            }
          });
          xhr.addEventListener('error', (e)=>{
              showError(errMsg);
          });
          xhr.open('GET', url, true);
          xhr.setRequestHeader('requesttoken', requesttoken);
          xhr.send();
        }
      },
      true
    );
  }
  buttonRolloverPrep.addEventListener('click', prepRollover);

  // execute a SAML Certificate Rollover
  function execRollover(e) {
    e.preventDefault();
    const errMsg = t('eidlogin','Certificate Rollover could not be executed')
    var msg = t('eidlogin','This will remove the currently used certificates from the Service Provider SAML Metadata and activate the prepared certificates. Are you sure?')
    OC.dialogs.confirmDestructive(
      msg,
      t('eidlogin','Activate prepared certificates'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Yes'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
        if (result) {
            const url = generateUrl('/apps/eidlogin/settings/executerollover');
            var xhr = new XMLHttpRequest();
            xhr.addEventListener('load', (e)=>{
              var msg = JSON.parse(e.target.responseText).message;
              var certAct = JSON.parse(e.target.responseText).cert_act;
              var certActEnc = JSON.parse(e.target.responseText).cert_act_enc;
              if(e.target.status == 200) {
                certActDiv.innerText = '... '+certAct;
                certActEncDiv.innerText = '... '+certActEnc;
                certNewDiv.innerText = t('eidlogin','No new certificate prepared yet.');
                certNewEncDiv.innerText = t('eidlogin','No new certificate prepared yet.');
                buttonRolloverExec.disabled = true;
                spanRolloverExec.classList.remove('hidden');
                showSuccess(msg);
              } else {
                showError(msg);
              }
            });
            xhr.addEventListener('error', (e)=>{
                showError(errMsg);
            });
            xhr.open('GET', url, true);
            xhr.setRequestHeader('requesttoken', requesttoken);
            xhr.send();
          }
        },
        true
    );
  }
  buttonRolloverExec.addEventListener('click', execRollover);

  // decide which elements to show depending on state of settings and if TLS is present
  const settingsPresent = dataSrc.dataset.present;
  const certActPresent = dataSrc.dataset.act_cert;
  const certNewPresent = dataSrc.dataset.new_cert;
  if(window.location.protocol!=='https:') {
    document.getElementById('eidlogin-settings-notls').classList.remove('hidden');
  } else if(settingsPresent!=='true') {
    switchWizardPanel(1);
    manual.classList.add('hidden');
    wizard.classList.remove('hidden');
    // prefill SP EntityID in wizard
    document.getElementById('eidlogin-settings-form-wizard-sp_entity_id').value = window.location.protocol+'//'+window.location.host;
  } else {
    wizard.classList.add('hidden');
    manual.classList.remove('hidden');
    // decide about rollover div
    if(certActPresent==='true') {
      document.getElementById('eidlogin-settings-manual-div-rollover').classList.remove('hidden');
    }
    // decide about showing new cert and key rollover execute button state
    if(certNewPresent==='true') {
      buttonRolloverExec.disabled = false;
      spanRolloverExec.classList.add('hidden');
    }
  }
  document.getElementById('eidlogin-settings-spinner').classList.add('hidden');

}); // DOMContentLoaded

// Force the page to be scrolled to top on reload
// otherwise we might hide elements after wizard use
window.onBeforeUnload = function () {
  document.documentElement.scrollTop=0;
  document.body.scrollTop=0;
}
