//** js for the personal settings of the eidlogin nextcloud app */
import '@nextcloud/dialogs/styles/toast.scss'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { getRequestToken } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

document.addEventListener('DOMContentLoaded', function(event) {

  const userHasEidSpan = document.getElementById('eidlogin-settings-span-user-has-eid');
  // if no info about eid in template, app is not activated, template will show message
  if (!userHasEidSpan) {
    return;
  }

  const hintCreate = document.getElementById('eidlogin-settings-span-hint-create').innerText;
  const hintDelete = document.getElementById('eidlogin-settings-span-hint-delete').innerText;
  const btnTextCreate = document.getElementById('eidlogin-settings-span-btntext-create').dataset.btntextCreate;
  const btnTextDelete = document.getElementById('eidlogin-settings-span-btntext-delete').dataset.btntextDelete;
  const hintEid = document.getElementById('eidlogin-settings-hint-eid');
  const hintButton = document.getElementById('eidlogin-settings-hint-button');
  const noPwLoginDiv = document.getElementById('eidlogin-settings-div-no_pw_login');
  const noPwLoginInput = document.getElementById('eidlogin-settings-input-no_pw_login');
  const noPwLoginLabel = document.getElementById('eidlogin-settings-label-no_pw_login');
  const buttonEid = document.getElementById('eidlogin-settings-button-eid');

  // show Create stuff
  function showCreate() {
    hintEid.innerText = hintCreate;
    hintButton.classList.add("hidden");
    buttonEid.innerText = btnTextCreate;
    buttonEid.removeEventListener('click', deleteEid);
    buttonEid.addEventListener('click', createEid);
    noPwLoginLabel.removeEventListener('click', toogleNoPwLogin);
    noPwLoginInput.disabled = true;
  }

  // show Delete stuff
  function showDelete() {
    hintEid.innerText = hintDelete;
    hintButton.classList.remove("hidden");
    buttonEid.innerText = btnTextDelete;
    buttonEid.removeEventListener('click', createEid);
    buttonEid.addEventListener('click', deleteEid);
    noPwLoginLabel.addEventListener('click', toogleNoPwLogin);
    noPwLoginInput.disabled = false;
  }

  // createEid
  function createEid() {
    var requesttoken = getRequestToken();
    var url = generateUrl('/apps/eidlogin/eid/createeid');
    url += '?requesttoken='+encodeURIComponent(requesttoken)
    window.location.href=url;
  }

  // deleteEid
  function deleteEid() {
    OC.dialogs.confirmDestructive(
      t('eidlogin','After the deletion you will not be able to access your account via eID-Login anymore. Are you sure?'),
      t('eidlogin','Delete connection to eID'),
      {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('eidlogin', 'Yes'),
        confirmClasses: 'error',
        cancel: t('eidlogin', 'Cancel')
      },
      (result) => {
      if (result) {
            const errMsg = t('eidlogin','Failed to delete the eID connection');
            var url = generateUrl('/apps/eidlogin/eid/deleteeid');
            var xhr = new XMLHttpRequest();
            var requesttoken = getRequestToken();
            xhr.addEventListener('load', (e)=>{
              var status = JSON.parse(e.target.responseText).status;
              var msg = JSON.parse(e.target.responseText).message;
              if(e.target.status == 200 && status == "success") {
                noPwLoginInput.checked = false;
                noPwLoginDiv.classList.add('hidden');
                showCreate();
                showSuccess(t('eidlogin',msg));
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

  // toggle value of the no_pw_login config value
  function toogleNoPwLogin() {
    const errMsg = t('eidlogin','Settings could not be saved');
    var url = generateUrl('/apps/eidlogin/settings/togglenopwlogin');
    var xhr = new XMLHttpRequest();
    var requesttoken = getRequestToken();
    xhr.addEventListener('load', (e)=>{
      var msg = JSON.parse(e.target.responseText).message;
      if(e.target.status == 200) {
        showSuccess(msg);
      } else if(e.target.status == 409) {
        showError(msg);
      } else {
        noPwLoginInput.checked = false;
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

  // define which button and text to show and which action to bind to button
  const userHasEid = userHasEidSpan.dataset.userHasEid;
  if (userHasEid) {
    showDelete();
  } else {
    showCreate();
  }

  // do we have saml result and msg after coming from a saml flow?
  const samlResult = document.getElementById('eidlogin-settings-span-saml-result').dataset.samlResult;
  const samlMsg = document.getElementById('eidlogin-settings-span-saml-msg').dataset.samlMsg;
  if (samlResult=='success' && samlMsg!='') {
        showSuccess(samlMsg);
  }
  if (samlResult=='error' && samlMsg!='') {
        showError(samlMsg);
  }

  // show the settings
  document.getElementById('eidlogin-settings-div-eid').classList.remove('hidden');

}); // DOMContentLoaded
