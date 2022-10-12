/// <reference types="cypress" />
const parser = require('fast-xml-parser');

/**
 * test the eID admin settings
 */
const prefix = '#eidlogin-settings-'
const skidMetaUrl = 'https://service.skidentity.de/fs/saml/metadata'

describe('admin related eID stuff', () => {
  beforeEach(() => {
    cy.task('dbClear')
    cy.visit('/login');
    cy.get('.eidlogin-login-button').should('not.exist')
    cy.login('admin','adminP396');
  });

  it('use wizard back and forth', () => {
    // fetch SkIDentity metadata for later comparison
    cy.request(skidMetaUrl).then((response) => {
      var idpMetadata = parser.parse(response.body, { ignoreAttributes: false })
      var nsFirst = ''
      Object.keys(idpMetadata).forEach((key) => {
          nsFirst = key.match('.*(?=:)')[0]
      })
      var entDesc = idpMetadata[nsFirst+":EntityDescriptor"]
      var nsSecond = '';
      Object.keys(entDesc).forEach((key) => {
        var match = key.match('.*(?=:)')
        if (match!=null) {
          nsSecond = match[0]
          return
        }
      })
      cy.wrap(entDesc["@_entityID"]).as("idp_entity_id")
      var ssoDesc = entDesc[nsSecond+":IDPSSODescriptor"]
      cy.wrap(ssoDesc[nsSecond+":SingleSignOnService"][0]["@_Location"]).as("idp_sso_url")
      var keyDesc = ssoDesc[nsSecond+":KeyDescriptor"]

      Object.keys(keyDesc).forEach((key) => {
        if (keyDesc[key]["@_use"] == "signing") {
          cy.wrap(keyDesc[key].KeyInfo.X509Data.X509Certificate).as('idp_cert_sign')
        }
        if (keyDesc[key]["@_use"] == "encryption") {
          cy.wrap(keyDesc[key].KeyInfo.X509Data.X509Certificate).as('idp_cert_enc')
        }
      });
    });
    cy.visit('/settings/admin/eidlogin');
    // overview panel at start
    cy.get(`${prefix}wizard`).should('be.visible')
    cy.get(`${prefix}wizard-panel-1`).should('be.visible')
    // show/hide info panel
    cy.get(`${prefix}wizard-panel-help`).should('not.be.visible')
    cy.get(`${prefix}button-help`).should('not.have.class', 'active')
    cy.get(`${prefix}button-help`).click()
    cy.get(`${prefix}wizard-panel-help`).should('be.visible')
    cy.get(`${prefix}button-help`).should('have.class', 'active')
    cy.get(`${prefix}button-help`).click()
    cy.get(`${prefix}wizard-panel-help`).should('not.be.visible')
    cy.get(`${prefix}button-help`).click()
    cy.get(`${prefix}wizard-panel-help`).should('be.visible')
    cy.get(`${prefix}button-close-help`).click()
    cy.get(`${prefix}wizard-panel-help`).should('not.be.visible')
    // check wizard navigation steps
    cy.get(`${prefix}wizard-step-3`).should('have.class', 'disabled')
    cy.get(`${prefix}wizard-step-4`).should('have.class', 'disabled')
    cy.get(`${prefix}wizard-step-2`).click()
    cy.get(`${prefix}wizard-step-3`).should('not.have.class', 'disabled')
    cy.get(`${prefix}wizard-step-4`).should('have.class', 'disabled')
    cy.get(`${prefix}wizard-panel-2`).should('be.visible')
    cy.get(`${prefix}wizard-step-1`).click()
    cy.get(`${prefix}wizard-panel-1`).should('be.visible')
    // configure IDP with skid metadataurl and check fetched values
    cy.get(`${prefix}button-next-2`).click()
    cy.get(`${prefix}wizard-panel-2`).should('be.visible')
    cy.get(`${prefix}form-wizard-sp_entity_id`).should("have.value", Cypress.config().baseUrl)
    cy.get(`${prefix}form-wizard-sp_enforce_enc`).should("not.be.checked")
    cy.get(`${prefix}form-wizard-idp_metadata_url`).type(skidMetaUrl);
    cy.get(`${prefix}button-toggleidp`).click()
    cy.get(`${prefix}wizard-panel-idp_settings`).should('be.visible')
    cy.get("@idp_entity_id").then((idp_entity_id) => {
      cy.get(`${prefix}form-wizard-idp_entity_id`).should("have.value", idp_entity_id)
    });
    cy.get("@idp_sso_url").then((idp_sso_url) => {
      cy.get(`${prefix}form-wizard-idp_sso_url`).should("have.value", idp_sso_url)
    });
    cy.get("@idp_cert_sign").then((idp_cert_sign) => {
      cy.get(`${prefix}form-wizard-idp_cert_sign`).should("have.value", idp_cert_sign)
    });
    cy.get("@idp_cert_enc").then((idp_cert_enc) => {
      cy.get(`${prefix}form-wizard-idp_cert_enc`).should("have.value", idp_cert_enc)
    });
    cy.get(`${prefix}form-wizard-idp_ext_tr03130`).should("be.empty")
    cy.get(`${prefix}button-toggleidp`).click()
    cy.get(`${prefix}wizard-panel-idp_settings`).should('not.be.visible')
    // fetched values should saved as they are valid and result in correct SP entityId
    cy.get(`${prefix}button-next-3`).click()
    cy.get(`${prefix}wizard-panel-3`).should('be.visible')
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    cy.get(`${prefix}wizard-display-sp_entity_id`).contains(Cypress.config().baseUrl)
    // test back buttons
    cy.get(`${prefix}button-back-2`).click()
    cy.get(`${prefix}wizard-panel-2`).should('be.visible')
    cy.get(`${prefix}button-back-1`).click()
    cy.get(`${prefix}wizard-panel-1`).should('be.visible')
    // use SKID Button, should result in valid and saved values also
    cy.get(`${prefix}button-select-skid`).click()
    cy.get(`${prefix}wizard-panel-3`).should('be.visible')
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // go back and check fetched values
    cy.get(`${prefix}button-back-2`).click()
    cy.get(`${prefix}wizard-panel-2`).should('be.visible')
    cy.get("@idp_entity_id").then((idp_entity_id) => {
      cy.get(`${prefix}form-wizard-idp_entity_id`).should("have.value", idp_entity_id)
    });
    cy.get("@idp_sso_url").then((idp_sso_url) => {
      cy.get(`${prefix}form-wizard-idp_sso_url`).should("have.value", idp_sso_url)
    });
    cy.get("@idp_cert_sign").then((idp_cert_sign) => {
      cy.get(`${prefix}form-wizard-idp_cert_sign`).should("have.value", idp_cert_sign)
    });
    cy.get("@idp_cert_enc").then((idp_cert_enc) => {
      cy.get(`${prefix}form-wizard-idp_cert_enc`).should("have.value", idp_cert_enc)
    });
    cy.get(`${prefix}form-wizard-idp_ext_tr03130`).should("be.empty")
    // proceed to last step with first aborting then confirming the security question
    cy.get(`${prefix}button-next-3`).click()
    cy.get(`${prefix}wizard-panel-3`).should('be.visible')
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    cy.get(`${prefix}button-next-4`).click()
    cy.get('#body-settings > div.oc-dialog > a.oc-dialog-close').click()
    cy.get(`${prefix}button-next-4`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow > button').first().click()
    cy.get(`${prefix}button-next-4`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow > button.error.primary').click()
    cy.get(`${prefix}wizard-panel-4`).should('be.visible')
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // finish configuration, should show manual mode
    cy.get(`${prefix}button-finish`).click()
    cy.get(`${prefix}manual`).should('be.visible')
    // check for eID-Login button
    cy.logout();
    cy.get('.eidlogin-login-button').should('be.visible')
  });

  it('test app (de)activation', () => {
    cy.task('dbSeed')
    cy.visit('/settings/admin/eidlogin');
    // deactivate app
    cy.get(`${prefix}input-activated`).should('be.checked')
    cy.get(`${prefix}label-activated`).click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // check if button is not visible
    cy.logout();
    cy.get('.eidlogin-login-button').should('not.exist')
    cy.login('admin','adminP396');
    cy.visit('/settings/admin/eidlogin');
    // activate app
    cy.get(`${prefix}label-activated`).click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // check if button is visible
    cy.logout();
    cy.get('.eidlogin-login-button').should('be.visible')
  });

  it('test manual config with form value validation', () => {
    cy.task('dbSeed')
    cy.visit('/settings/admin/eidlogin');
    // empty form results in 4 errors
    cy.get(`${prefix}form-manual-sp_entity_id`).clear()
    cy.get(`${prefix}form-manual-idp_entity_id`).clear()
    cy.get(`${prefix}form-manual-idp_sso_url`).clear()
    cy.get(`${prefix}form-manual-idp_cert_sign`).clear()
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,4);
    cy.get('.toast-close').click({ multiple: true });
    // filling sp_entity_id correctly
    cy.get(`${prefix}form-manual-sp_entity_id`).type(Cypress.config().baseUrl)
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,3);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_entity_id correctly
    cy.get(`${prefix}form-manual-idp_entity_id`).type('foobar')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,2);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_sso_url with invalid url
    cy.get(`${prefix}form-manual-idp_sso_url`).type('foobar')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,2);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_sso_url with non TLS url
    cy.get(`${prefix}form-manual-idp_sso_url`).clear()
    cy.get(`${prefix}form-manual-idp_sso_url`).type('http://foobar.com')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,2);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_sso_url correctly
    cy.get(`${prefix}form-manual-idp_sso_url`).clear()
    cy.get(`${prefix}form-manual-idp_sso_url`).type('https://foobar.com')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_cert_sign and idp_cert_enc incorrectly
    cy.get(`${prefix}form-manual-idp_cert_sign`).type('foobar')
    cy.get(`${prefix}form-manual-idp_cert_enc`).type('foobar')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,2);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_cert_sign and idp_cert_enc incorrectly
    cy.get(`${prefix}form-manual-idp_cert_sign`).clear()
    cy.get(`${prefix}form-manual-idp_cert_sign`).fill('MIIBKTCB1KADAgECAgRglScoMA0GCSqGSIb3DQEBCwUAMBwxGjAYBgNVBAMMEXRlc3QtY2VydCByc2EgNTEyMB4XDTIxMDUwNzExNDAyNFoXDTIyMDUwNzExNDAyNFowHDEaMBgGA1UEAwwRdGVzdC1jZXJ0IHJzYSA1MTIwXDANBgkqhkiG9w0BAQEFAANLADBIAkEA0LP4k6cbOL1xSs432wj9YB/TB3BkO7j7fxelkqJZNPTtWrMlj1L+3qpPAuGdhXkj689o38Rbk9yOpqq4FlN11QIDAQABMA0GCSqGSIb3DQEBCwUAA0EAo1xf6bJSmcBB9Q2URr7DM22GPeykJGwmAltR3nBeXvauzbS4syF+/cjVzEO+t8wCo+Ws7tfvcLCocUp+cOVZNQ==')
    cy.get(`${prefix}form-manual-idp_cert_enc`).clear()
    cy.get(`${prefix}form-manual-idp_cert_enc`).fill('MIIBKTCB1KADAgECAgRglScoMA0GCSqGSIb3DQEBCwUAMBwxGjAYBgNVBAMMEXRlc3QtY2VydCByc2EgNTEyMB4XDTIxMDUwNzExNDAyNFoXDTIyMDUwNzExNDAyNFowHDEaMBgGA1UEAwwRdGVzdC1jZXJ0IHJzYSA1MTIwXDANBgkqhkiG9w0BAQEFAANLADBIAkEA0LP4k6cbOL1xSs432wj9YB/TB3BkO7j7fxelkqJZNPTtWrMlj1L+3qpPAuGdhXkj689o38Rbk9yOpqq4FlN11QIDAQABMA0GCSqGSIb3DQEBCwUAA0EAo1xf6bJSmcBB9Q2URr7DM22GPeykJGwmAltR3nBeXvauzbS4syF+/cjVzEO+t8wCo+Ws7tfvcLCocUp+cOVZNQ==')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,2);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_cert_sign and idp_cert_enc correctly
    cy.get(`${prefix}form-manual-idp_cert_sign`).clear()
    cy.get(`${prefix}form-manual-idp_cert_sign`).fill('MIIFlzCCA3+gAwIBAgIINK3wkhEt4oowDQYJKoZIhvcNAQELBQAwYzELMAkGA1UEBhMCREUxDzANBgNVBAgTBkJheWVybjERMA8GA1UEBxMITWljaGVsYXUxEzARBgNVBAoTCmVjc2VjIEdtYkgxGzAZBgNVBAMTElNrSURlbnRpdHkgU0FNTCBGUzAeFw0yMTEyMTMxMDAwMDBaFw0yNDAyMTMxMDAwMDBaMGMxCzAJBgNVBAYTAkRFMQ8wDQYDVQQIEwZCYXllcm4xETAPBgNVBAcTCE1pY2hlbGF1MRMwEQYDVQQKEwplY3NlYyBHbWJIMRswGQYDVQQDExJTa0lEZW50aXR5IFNBTUwgRlMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQCgSraq4/BaSD+8tPKKsez/Uk6FZ2c4cxSzjvcZptVPo7IH2cdLRKnlVfVgLPoeV+MOL/viu1y6IPp6aEJ09vl/7V0P5oEZ9BJ41K6DVsBb/puiFOC/Ma6Q53DbHbZQJJdGPmX1RH297e420iYs19zH7Y98X+ZTVOlOIxc26/yubc6XiMPvGzIv5BsHYzfyLFdapV/PTj21BDUmhas/H83zJP1IGdurJOt8/u7T1Mg2haLlU+Vp1xdeSaZgk+iesRyIB3Y774s6jqavxkit9PHk+Qq166sW2NOQLtb/BR/1aVK5rvvQqrZ0cLnk2jCFyDht4kZ7O6T5C0seQXDOGKHacv6neqfLu+4lWOTpZk/ANrbd8d2oG98k8lc5j2agVC7PjM0lTRoEMedTfG7J4q4mgSKhlL+YrRhIb/nYUSScn0EiAr32YSb5caboT3+eiqXnzAqVbH/wtwXIpbTkgQEwlk6A/TkDhv9+ssDv75k4PUKWmFjUKrC/TUQmC5k8TXvO40NX2cGOVimTavN1fSe1Pj1ytmQXRrbfrKiNwz+EbhAJHTdkEHh40XwjJh2jvwSSctvs3vpVIAtX4FPtHTOraBCZyyH0X/1vtKRruY2VzO8kAeU2Zb4NWE2STmFSXbIG9Pyci9eqdtd5nr3GaPj4g8BabcmMweOJRWwqm8F3fwIDAQABo08wTTAdBgNVHQ4EFgQUPSTV0I2z0mB0eJ/2JPvLPb4UVxswHwYDVR0jBBgwFoAUPSTV0I2z0mB0eJ/2JPvLPb4UVxswCwYDVR0PBAQDAgSQMA0GCSqGSIb3DQEBCwUAA4ICAQBWc4IQBece9ZXmkEe1SXGkg3ZqWNNJlkO4LuJOyDudLLPebjAM9JLBl1MY4Fnn9j2+ZeJHP9JRp4Igw49lGEI6KX/oGeDr+VfxHdRQ4mHs54JUKDcUef10xwlZ0sxX7bStNXtKOfMsaftwS/UfbjqawCQxXWMRONDMJVZXDE1ZrgvVC2/547AXJX93HtfTTPj8o3doEIF6IOBS9bjRZ6GUilzePsj3OaTbbGRHlGvxrBXmzZljF0wVmcBm6VneP0Ltap09Wwj2DI5n3PFGze4ufAj2UvkoJAlmOqnDKMcCMt8km9TkZtO1HtePCRj6n/FYWU33FB78gt1ZNrsYSWHAuco1irYUBg9wi6pJ/tJ4VwBk1astVrKTrJvMrvSIQeAzOhQ4DN+Rmv3CPvDshlrNxgC6HGvymSaOLRLX0gS0FbJmYgriXpy6AzSIkNqP4Fl9wT7MY0wYE3/bTuDO2Q/DcFif0AVn8AZHr9jM1H8SzzykkHgNvMQi1bHOv34WK6pYfuCD8/5f/OHf1LBADX5BHdu69vN9kc0LBdreLEysuqCTXTLov2h8osupsM1MDPrglm82PCJVcQ0zpwIBJiV7weDPqmibMqo7zDHRvFfrdqsfqVDdpwEex17kmqV+hYgufB4+uAr7E/crGd0YTv+SmySz1zxeoSZJn+f7cIfYFw==')
    cy.get(`${prefix}form-manual-idp_cert_enc`).clear()
    cy.get(`${prefix}form-manual-idp_cert_enc`).fill('MIIFlzCCA3+gAwIBAgIINK3wkhEt4oowDQYJKoZIhvcNAQELBQAwYzELMAkGA1UEBhMCREUxDzANBgNVBAgTBkJheWVybjERMA8GA1UEBxMITWljaGVsYXUxEzARBgNVBAoTCmVjc2VjIEdtYkgxGzAZBgNVBAMTElNrSURlbnRpdHkgU0FNTCBGUzAeFw0yMTEyMTMxMDAwMDBaFw0yNDAyMTMxMDAwMDBaMGMxCzAJBgNVBAYTAkRFMQ8wDQYDVQQIEwZCYXllcm4xETAPBgNVBAcTCE1pY2hlbGF1MRMwEQYDVQQKEwplY3NlYyBHbWJIMRswGQYDVQQDExJTa0lEZW50aXR5IFNBTUwgRlMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQCgSraq4/BaSD+8tPKKsez/Uk6FZ2c4cxSzjvcZptVPo7IH2cdLRKnlVfVgLPoeV+MOL/viu1y6IPp6aEJ09vl/7V0P5oEZ9BJ41K6DVsBb/puiFOC/Ma6Q53DbHbZQJJdGPmX1RH297e420iYs19zH7Y98X+ZTVOlOIxc26/yubc6XiMPvGzIv5BsHYzfyLFdapV/PTj21BDUmhas/H83zJP1IGdurJOt8/u7T1Mg2haLlU+Vp1xdeSaZgk+iesRyIB3Y774s6jqavxkit9PHk+Qq166sW2NOQLtb/BR/1aVK5rvvQqrZ0cLnk2jCFyDht4kZ7O6T5C0seQXDOGKHacv6neqfLu+4lWOTpZk/ANrbd8d2oG98k8lc5j2agVC7PjM0lTRoEMedTfG7J4q4mgSKhlL+YrRhIb/nYUSScn0EiAr32YSb5caboT3+eiqXnzAqVbH/wtwXIpbTkgQEwlk6A/TkDhv9+ssDv75k4PUKWmFjUKrC/TUQmC5k8TXvO40NX2cGOVimTavN1fSe1Pj1ytmQXRrbfrKiNwz+EbhAJHTdkEHh40XwjJh2jvwSSctvs3vpVIAtX4FPtHTOraBCZyyH0X/1vtKRruY2VzO8kAeU2Zb4NWE2STmFSXbIG9Pyci9eqdtd5nr3GaPj4g8BabcmMweOJRWwqm8F3fwIDAQABo08wTTAdBgNVHQ4EFgQUPSTV0I2z0mB0eJ/2JPvLPb4UVxswHwYDVR0jBBgwFoAUPSTV0I2z0mB0eJ/2JPvLPb4UVxswCwYDVR0PBAQDAgSQMA0GCSqGSIb3DQEBCwUAA4ICAQBWc4IQBece9ZXmkEe1SXGkg3ZqWNNJlkO4LuJOyDudLLPebjAM9JLBl1MY4Fnn9j2+ZeJHP9JRp4Igw49lGEI6KX/oGeDr+VfxHdRQ4mHs54JUKDcUef10xwlZ0sxX7bStNXtKOfMsaftwS/UfbjqawCQxXWMRONDMJVZXDE1ZrgvVC2/547AXJX93HtfTTPj8o3doEIF6IOBS9bjRZ6GUilzePsj3OaTbbGRHlGvxrBXmzZljF0wVmcBm6VneP0Ltap09Wwj2DI5n3PFGze4ufAj2UvkoJAlmOqnDKMcCMt8km9TkZtO1HtePCRj6n/FYWU33FB78gt1ZNrsYSWHAuco1irYUBg9wi6pJ/tJ4VwBk1astVrKTrJvMrvSIQeAzOhQ4DN+Rmv3CPvDshlrNxgC6HGvymSaOLRLX0gS0FbJmYgriXpy6AzSIkNqP4Fl9wT7MY0wYE3/bTuDO2Q/DcFif0AVn8AZHr9jM1H8SzzykkHgNvMQi1bHOv34WK6pYfuCD8/5f/OHf1LBADX5BHdu69vN9kc0LBdreLEysuqCTXTLov2h8osupsM1MDPrglm82PCJVcQ0zpwIBJiV7weDPqmibMqo7zDHRvFfrdqsfqVDdpwEex17kmqV+hYgufB4+uAr7E/crGd0YTv+SmySz1zxeoSZJn+f7cIfYFw==')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_ext_tr03130 incorrectly with missing idp_cert_enc and missing sp_enforce_enc
    cy.get(`${prefix}form-manual-sp_enforce_enc`).uncheck({force: true})
    cy.get(`${prefix}form-manual-idp_cert_enc`).clear()
    cy.get(`${prefix}form-manual-idp_ext_tr03130`).type('foobar')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-error').length,3);
    cy.get('.toast-close').click({ multiple: true });
    // filling idp_ext_tr03130 correctly (well, it is xml)
    cy.get(`${prefix}form-manual-sp_enforce_enc`).check({force: true})
    cy.get(`${prefix}form-manual-idp_cert_enc`).fill('MIIFlzCCA3+gAwIBAgIINK3wkhEt4oowDQYJKoZIhvcNAQELBQAwYzELMAkGA1UEBhMCREUxDzANBgNVBAgTBkJheWVybjERMA8GA1UEBxMITWljaGVsYXUxEzARBgNVBAoTCmVjc2VjIEdtYkgxGzAZBgNVBAMTElNrSURlbnRpdHkgU0FNTCBGUzAeFw0yMTEyMTMxMDAwMDBaFw0yNDAyMTMxMDAwMDBaMGMxCzAJBgNVBAYTAkRFMQ8wDQYDVQQIEwZCYXllcm4xETAPBgNVBAcTCE1pY2hlbGF1MRMwEQYDVQQKEwplY3NlYyBHbWJIMRswGQYDVQQDExJTa0lEZW50aXR5IFNBTUwgRlMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQCgSraq4/BaSD+8tPKKsez/Uk6FZ2c4cxSzjvcZptVPo7IH2cdLRKnlVfVgLPoeV+MOL/viu1y6IPp6aEJ09vl/7V0P5oEZ9BJ41K6DVsBb/puiFOC/Ma6Q53DbHbZQJJdGPmX1RH297e420iYs19zH7Y98X+ZTVOlOIxc26/yubc6XiMPvGzIv5BsHYzfyLFdapV/PTj21BDUmhas/H83zJP1IGdurJOt8/u7T1Mg2haLlU+Vp1xdeSaZgk+iesRyIB3Y774s6jqavxkit9PHk+Qq166sW2NOQLtb/BR/1aVK5rvvQqrZ0cLnk2jCFyDht4kZ7O6T5C0seQXDOGKHacv6neqfLu+4lWOTpZk/ANrbd8d2oG98k8lc5j2agVC7PjM0lTRoEMedTfG7J4q4mgSKhlL+YrRhIb/nYUSScn0EiAr32YSb5caboT3+eiqXnzAqVbH/wtwXIpbTkgQEwlk6A/TkDhv9+ssDv75k4PUKWmFjUKrC/TUQmC5k8TXvO40NX2cGOVimTavN1fSe1Pj1ytmQXRrbfrKiNwz+EbhAJHTdkEHh40XwjJh2jvwSSctvs3vpVIAtX4FPtHTOraBCZyyH0X/1vtKRruY2VzO8kAeU2Zb4NWE2STmFSXbIG9Pyci9eqdtd5nr3GaPj4g8BabcmMweOJRWwqm8F3fwIDAQABo08wTTAdBgNVHQ4EFgQUPSTV0I2z0mB0eJ/2JPvLPb4UVxswHwYDVR0jBBgwFoAUPSTV0I2z0mB0eJ/2JPvLPb4UVxswCwYDVR0PBAQDAgSQMA0GCSqGSIb3DQEBCwUAA4ICAQBWc4IQBece9ZXmkEe1SXGkg3ZqWNNJlkO4LuJOyDudLLPebjAM9JLBl1MY4Fnn9j2+ZeJHP9JRp4Igw49lGEI6KX/oGeDr+VfxHdRQ4mHs54JUKDcUef10xwlZ0sxX7bStNXtKOfMsaftwS/UfbjqawCQxXWMRONDMJVZXDE1ZrgvVC2/547AXJX93HtfTTPj8o3doEIF6IOBS9bjRZ6GUilzePsj3OaTbbGRHlGvxrBXmzZljF0wVmcBm6VneP0Ltap09Wwj2DI5n3PFGze4ufAj2UvkoJAlmOqnDKMcCMt8km9TkZtO1HtePCRj6n/FYWU33FB78gt1ZNrsYSWHAuco1irYUBg9wi6pJ/tJ4VwBk1astVrKTrJvMrvSIQeAzOhQ4DN+Rmv3CPvDshlrNxgC6HGvymSaOLRLX0gS0FbJmYgriXpy6AzSIkNqP4Fl9wT7MY0wYE3/bTuDO2Q/DcFif0AVn8AZHr9jM1H8SzzykkHgNvMQi1bHOv34WK6pYfuCD8/5f/OHf1LBADX5BHdu69vN9kc0LBdreLEysuqCTXTLov2h8osupsM1MDPrglm82PCJVcQ0zpwIBJiV7weDPqmibMqo7zDHRvFfrdqsfqVDdpwEex17kmqV+hYgufB4+uAr7E/crGd0YTv+SmySz1zxeoSZJn+f7cIfYFw==')
    cy.get(`${prefix}form-manual-idp_ext_tr03130`).clear()
    cy.get(`${prefix}form-manual-idp_ext_tr03130`).type('<foo>bar</foo>')
    cy.get(`${prefix}button-manual-save`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    // logout and check for eID-Login button
    cy.logout();
    cy.get('.eidlogin-login-button').should('be.visible')
  });

  /*
  it('reset settings', () => {
    cy.task('dbSeed')
    cy.visit('/settings/admin/eidlogin');
    cy.get(`${prefix}manual`).should('be.visible')
    cy.get(`${prefix}button-reset`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    cy.get(`${prefix}wizard`).should('be.visible')
    cy.logout();
    cy.get('.eidlogin-login-button').should('not.exist')
  });

  it('certificate rollover', () => {
    cy.task('dbSeed')
    cy.visit('/settings/admin/eidlogin');
    cy.get(`${prefix}manual`).should('be.visible')
    cy.get(`${prefix}manual-div-cert-new`).contains('...').should('not.exist')
    cy.get(`${prefix}manual-div-cert-new-enc`).contains('...').should('not.exist')
    cy.get(`${prefix}button-rollover-execute`).should('be.disabled')
    cy.get(`${prefix}button-rollover-prepare`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    cy.get(`${prefix}manual-div-cert-new`).contains('...')
    cy.get(`${prefix}manual-div-cert-new-enc`).contains('...')
    cy.get(`${prefix}manual-div-cert-new`).invoke('text').as("certNew")
    cy.get(`${prefix}manual-div-cert-new-enc`).invoke('text').as("certNewEnc")
    cy.get(`${prefix}button-rollover-execute`).should('be.enabled')
    cy.get(`${prefix}button-rollover-execute`).click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-success').length,1);
    cy.get('.toast-close').click({ multiple: true });
    cy.get(`${prefix}manual-div-rollover`).scrollIntoView()
    cy.get("@certNew").then((certNew) => {
      cy.get(`${prefix}manual-div-cert-act`).invoke('text').then(txt => {expect(txt).to.equal(certNew)})
    });
    cy.get("@certNewEnc").then((certNewEnc) => {
      cy.get(`${prefix}manual-div-cert-act-enc`).invoke('text').then(txt => {expect(txt).to.equal(certNewEnc)})
    });
    cy.get(`${prefix}manual-div-cert-new`).contains('...').should('not.exist')
    cy.get(`${prefix}manual-div-cert-new-enc`).contains('...').should('not.exist')
    cy.logout();
    cy.get('.eidlogin-login-button').should('be.visible')
  });
  */
});
