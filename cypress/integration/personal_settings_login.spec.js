/// <reference types="cypress" />

/**
 * Test the eID personal settings and the login.
 * Please note: holds state between tests and needs user interaction.
 * Can only be run in non headless Electron browser!
 */
const username = 'testuser';
const password = 'testuser993882719';
const waitForSkidInMs = 10000;

describe('user related eID stuff', () => {

  before(() => {
    if (Cypress.browser.displayName!=="Electron" || Cypress.browser.isHeadless) {
      throw new Error('Tests need interaction with the skidentity system and works only in non headless Electron')
    }
    cy.log('*********************************************')
    cy.log("IMPORTANT: Make sure that the nameID you use is not assigned by another user!");
    cy.log('ATTENTION: These tests need user interaction!')
    cy.log('Please follow the instructions in the log messages!')
    cy.log('*********************************************')
    cy.task('dbClear')
    cy.deleteUser(username, password);
    cy.createUser(username, password);
    cy.login(username,password);
    cy.waitUntil(() => cy.get('#firstrunwizard button.icon-close').then($button => {
      $button.is(':visible')
    }), {
      interval: 1000
    });
    cy.intercept('DELETE','/apps/firstrunwizard/wizard').as('closeFirstRunWizard')
    cy.get('#firstrunwizard button.icon-close').click({force: true})
    cy.wait('@closeFirstRunWizard')
    cy.logout();
  })

  it('show no button to create eID', () => {
    cy.login(username,password);
    cy.task('dbClear')
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-button-eid').should('not.exist')
    cy.logout();
  })

  it('create eID fail, create eID success', () => {
    cy.task('dbSeed')
    cy.login(username,password);
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-input-no_pw_login').should('not.be.visible')
    cy.get('#eidlogin-settings-button-eid').click()
    cy.log('waiting for skidentity ...')
    cy.log('*********************************************')
    cy.log('ATTENTION: If you dont have a CloudId in the cypress used browser profile, please create it now and restart the test ...')
    cy.log('OTHERWISE ... , please ABORT at idp ...')
    cy.log('*********************************************')
    cy.waitUntil(() => cy.get('#eidlogin-settings-button-eid').then($button => {
      $button.is(':visible')
    }), {
      timeout: waitForSkidInMs,
      interval: 1000
    });
    expect(cy.get('.toast-error').length,1);

    cy.get('#eidlogin-settings-button-eid').click()
    cy.log('*********************************************')
    cy.log('ATTENTION: Please ENTER CORRECT PIN at idp ...')
    cy.log('*********************************************')
    cy.waitUntil(() => cy.get('#eidlogin-settings-button-eid').then($button => {
      $button.is(':visible')
    }), {
      timeout: waitForSkidInMs,
      interval: 1000
    });
    expect(cy.get('.toast-success').length,1);
    cy.logout();
  })

  it('login with eID abort', () => {
    cy.visit('/login');
    cy.log('*********************************************')
    cy.log('ATTENTION: Please ABORT at idp ...')
    cy.log('*********************************************')
    cy.get('.eidlogin-login-button').click()
    cy.waitUntil(() => cy.get('#body-login').then($div => {
      $div.is(':visible')
    }), {
      timeout: waitForSkidInMs,
      interval: 1000
    });
	  cy.get('#body-login div.warning').should('be.visible')
  })

  it('login with eID success', () => {
    cy.visit('/login');
    // returning false here prevents Cypress from failing the test because of js error in nc code
    Cypress.on('uncaught:exception', (err, runnable) => {
      return false
    })
    cy.log('*********************************************')
    cy.log('ATTENTION: Please ENTER CORRECT PIN at idp ...')
    cy.log('*********************************************')
    cy.get('.eidlogin-login-button').click()
    cy.waitUntil(() => cy.url().then($url => {
      $url === Cypress.config().baseUrl+'/apps/dashboard/'
    }), {
      timeout: waitForSkidInMs,
      interval: 1000
    });
	  cy.url().should('include', '/apps/dashboard')
    cy.logout();
  })

  it('delete eID', () => {
    cy.login(username,password);
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-button-eid').click()
    cy.get('#body-settings > div.oc-dialog > div.oc-dialog-buttonrow.twobuttons > button.error.primary').click()
    expect(cy.get('.toast-success').length,1);
    cy.get('#eidlogin-settings-input-no_pw_login').should('be.not.visible')
    cy.get('#eidlogin-settings-button-eid').should('be.visible')
    cy.logout();
    cy.task('dbClear')
  })

  it('login with eID fail, create eID after login fail with nameID from session', () => {
    cy.task('dbSeed')
    cy.visit('/login');
    cy.log('*********************************************')
    cy.log('ATTENTION: Please ENTER CORRECT PIN at idp ...')
    cy.log('*********************************************')
    cy.get('.eidlogin-login-button').click()
    cy.waitUntil(() => cy.get('#body-login').then($div => {
      $div.is(':visible')
    }), {
      timeout: waitForSkidInMs,
      interval: 1000
    });
	  cy.get('#body-login div.warning').should('be.visible')
    cy.login(username,password);
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-button-eid').click()
    expect(cy.get('.toast-success').length,1);
    cy.logout()
  })

  it('prevent password based login', () => {
    cy.login(username,password);
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-input-no_pw_login').should('not.be.disabled')
    cy.get('#eidlogin-settings-label-no_pw_login').click()
    expect(cy.get('.toast-error').length,1);
    cy.visit('/settings/user');
    cy.intercept('PUT','/ocs/v2.php/cloud/users/testuser').as('userSettings')
    cy.get('div.email input').type('admin@admin.admin', { force: true })
    cy.get('#displayname').focus()
    cy.wait('@userSettings')
    cy.visit('/settings/user/security');
    cy.get('#eidlogin-settings-label-no_pw_login').click()
    expect(cy.get('.toast-success').length,1);

    cy.logout();
    cy.visit('/');
    cy.get('input#user').type(username)
    cy.get('input#password').type(password)
    cy.get('input#submit-form').click()
	  cy.get('#body-login div.error').should('be.visible')
  })

  after(() => {
    cy.task('dbClear')
    cy.deleteUser(username, password);
  });
});