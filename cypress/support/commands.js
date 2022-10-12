// ***********************************************
// This commands.js creates various custom commands
//
// inspired by https://github.com/nextcloud/viewer/blob/master/cypress/support/commands.js
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

import 'cypress-wait-until';

const auth = {
	user: 'admin',
	pass: 'adminP396'
}

Cypress.Commands.add('login', (user, password, route = '/apps/files') => {
	cy.clearCookies()
	Cypress.Cookies.defaults({
		preserve: /^(oc|nc)/,
	})
	cy.visit(route)
	cy.get('input[name=user]').type(user)
	cy.get('input[name=password]').type(password)
    // Make it work with Nextcloud 24 and 25.
	cy.get('input[type=submit],button[type=submit]').click()
	cy.url().should('include', route)
})

Cypress.Commands.add('logout', () => {
	cy.get('#settings').click()
    cy.get('#expanddiv li[data-id="logout"] a').click()
	cy.url().should('include', 'login')
})

Cypress.Commands.add('createUser', (user, password) => {
	cy.clearCookies()
	cy.request({
		method: 'POST',
		url: `${Cypress.config('baseUrl')}/ocs/v1.php/cloud/users?format=json`,
		form: true,
		body: {
			userid: user,
			password,
		},
		auth: auth,
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
			'Authorization': `Basic ${btoa('admin:admin')}`,
		},
	}).then(response => {
		cy.log(JSON.stringify(response.body.ocs.meta));
		expect(response.body.ocs.meta.statuscode).to.equal(100)
		cy.log(`Created user ${user}`)

	})
})

Cypress.Commands.add('deleteUser', (user) => {
	Cypress.on('uncaught:exception', (err, runnable) => {
		// returning false here prevents Cypress from
		// failing the test
		return false
	})
	cy.clearCookies()
	cy.request({
		method: 'DELETE',
		url: `${Cypress.config('baseUrl')}/ocs/v1.php/cloud/users/${user}?format=json`,
		auth: auth,
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
			'Authorization': `Basic ${btoa('admin:admin')}`,
		},
	}).then(response => {
		if (response.body.ocs.meta.statuscode === 100) {
			cy.log(`Deleted user ${user}`)
		} else {
			cy.log(`No user ${user} deleted`)
		}
	})
})

// https://github.com/cypress-io/cypress/issues/566#issuecomment-474141037
Cypress.Commands.add('fill', {
	prevSubject: 'element'
  }, (subject, value) => {
	cy.wrap(subject).invoke('val', value).trigger('input').trigger('change')
});
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
