/// <reference types='cypress' />
// ***********************************************************
// This plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// fix problem with 'Cannot find module '@nextcloud/browserslist-config'''
// https://github.com/cypress-io/cypress/issues/2983#issuecomment-475266919
const browserify = require('@cypress/browserify-preprocessor')

// used for the db tasks
const db = require("mysql2/promise").createPool({
  host: 'localhost',
  port: '3310',
  user: 'p396ncuser',
  password: 'p396ncpass',
  database: 'p396ncdb'
})
const dbResultHandler = (err, results) => {
  if (err) console.log(err);
  console.log(results);
}

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)
/**
 * @type {Cypress.PluginConfig}
 */
module.exports = (on, config) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config
  const options = browserify.defaultOptions
  const envPreset = options.browserifyOptions.transform[1][1].presets[0]
  options.browserifyOptions.transform[1][1].presets[0] = [envPreset, { ignoreBrowserslistConfig: true }]
  on('file:preprocessor', browserify(options))

  // tasks for database handling
  on('task', {
    async dbSeed() {
      console.log('db seed ...');
      await db.execute(
        "INSERT INTO oc_appconfig (appid,configkey,configvalue) VALUES ('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?),('eidlogin',?,?);",
        [
          'activated',
          '1',
          'idp_entity_id',
          'https://service.skidentity.de/fs/saml/metadata',
          'idp_sso_url',
          'https://service.skidentity.de/fs/saml/remoteauth/',
          'idp_cert_sign',
          'MIIFlzCCA3+gAwIBAgIIUxbcS/Bb6QcwDQYJKoZIhvcNAQELBQAwYzELMAkGA1UEBhMCREUxDzANBgNVBAgTBkJheWVybjERMA8GA1UEBxMITWljaGVsYXUxEzARBgNVBAoTCmVjc2VjIEdtYkgxGzAZBgNVBAMTElNrSURlbnRpdHkgU0FNTCBGUzAeFw0yMDAxMTMxMDAwMDBaFw0yMjAxMTMxMDAwMDBaMGMxCzAJBgNVBAYTAkRFMQ8wDQYDVQQIEwZCYXllcm4xETAPBgNVBAcTCE1pY2hlbGF1MRMwEQYDVQQKEwplY3NlYyBHbWJIMRswGQYDVQQDExJTa0lEZW50aXR5IFNBTUwgRlMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQCgSraq4/BaSD+8tPKKsez/Uk6FZ2c4cxSzjvcZptVPo7IH2cdLRKnlVfVgLPoeV+MOL/viu1y6IPp6aEJ09vl/7V0P5oEZ9BJ41K6DVsBb/puiFOC/Ma6Q53DbHbZQJJdGPmX1RH297e420iYs19zH7Y98X+ZTVOlOIxc26/yubc6XiMPvGzIv5BsHYzfyLFdapV/PTj21BDUmhas/H83zJP1IGdurJOt8/u7T1Mg2haLlU+Vp1xdeSaZgk+iesRyIB3Y774s6jqavxkit9PHk+Qq166sW2NOQLtb/BR/1aVK5rvvQqrZ0cLnk2jCFyDht4kZ7O6T5C0seQXDOGKHacv6neqfLu+4lWOTpZk/ANrbd8d2oG98k8lc5j2agVC7PjM0lTRoEMedTfG7J4q4mgSKhlL+YrRhIb/nYUSScn0EiAr32YSb5caboT3+eiqXnzAqVbH/wtwXIpbTkgQEwlk6A/TkDhv9+ssDv75k4PUKWmFjUKrC/TUQmC5k8TXvO40NX2cGOVimTavN1fSe1Pj1ytmQXRrbfrKiNwz+EbhAJHTdkEHh40XwjJh2jvwSSctvs3vpVIAtX4FPtHTOraBCZyyH0X/1vtKRruY2VzO8kAeU2Zb4NWE2STmFSXbIG9Pyci9eqdtd5nr3GaPj4g8BabcmMweOJRWwqm8F3fwIDAQABo08wTTAdBgNVHQ4EFgQUPSTV0I2z0mB0eJ/2JPvLPb4UVxswHwYDVR0jBBgwFoAUPSTV0I2z0mB0eJ/2JPvLPb4UVxswCwYDVR0PBAQDAgSQMA0GCSqGSIb3DQEBCwUAA4ICAQCbquW0L2qylIajQ0IelyVQhhAQPc2Eu8ZYequg2OGWHD/LnMyQxEX7eCiIEXTy92+B1Yw9BWVPQo2LvIgzwNAOFaepbdZJCa9CfuI5BEJUlX4QlGZWMfoFIhT08//Z1op+ru4FeQEZwH6fVJqotTnxkpmjbAOMrC5UVpADqBoIoRdS0IaWjW2mN6Gt9G0priQxmgV3FC8n4dhYUgyndOG9ImYkgxtRwHGnk0SC/N6b3PMZxAccxDKBfY0vxAsg3Hktshc5LF2OW08o9Uji/w6OHvSL4uYVGkPOot6u1wncKsz8bQyt7Sj+Tx3nNdqjNciZsd11i9YlIlI0DmLCb4cq61P1AAAZY4d9ah0NdfWLNBUdeER4qnOahdwJXQXdMGkc4FNF4gx7gczGG4vrMKHgn8v2jxEuAhNHVbBGSi0JwO/eK/p8nFW8y/3SgXIWhL+efS4DWYcYhVKU7izAgj0fnnF/flUkaJjTH+rSgzQK/QISYplzSGPa0+bri/kxvxx1Q1VwPI1hpFAS/o9pFuANlNeBD6x26HZYJPK7Leg9/sQ+IAgkS8KR+GInyaZ285A1QNmBy7MmVU304WM6fiZ9+Osbi7n7aK6+BFbKFnhnVRTp4C7Vp3xCXut6z62q0BuxfiHvrYgA5X2HxPRuTjb+beHkiLq7VOb9AW8cPI4wHw==',
          'sp_entity_id',
          'https://nextcloud21.p396.de',
          'sp_cert_act',
          'MIIEHzCCAoegAwIBAgIEYJ5fwzANBgkqhkiG9w0BAQsFADAiMSAwHgYDVQQDDBdOZXh0Y2xvdWQgZUlELUxvZ2luIEFwcDAeFw0yMTA1MTQxMTMyMTlaFw0yMzA1MTQxMTMyMTlaMCIxIDAeBgNVBAMMF05leHRjbG91ZCBlSUQtTG9naW4gQXBwMIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEA8Pq3uT+hVkMzkzW4DSf5PH2lScvvBRHEBAgPzLbjthojXZFOTVQKVQvj2ngwugcYL6kzGVHFjder3/30WDE5iqncW/tIaoeIqyMeOokCr4EFaWg3FwQaxtGKQNbT6gjmFeoaJYIAHage8xpS6+N4He4z8isEh30X1D1qkNsH+tZ7Bv2pGf9juuGyvaDu96+AbQxSd9BcVM3SfjwdUFgQu99nEMKSmM/FFCPXE8zZ3M5lNnVoIMXYmy8wBlGF+lVft96ce39DagZzH6tj+V+8NRgk4jPaomhqfqpwOKUwgfgqEjtpaeVAZXWLsMUNeLjZ6cNYR6McpLg6xKnljhJbEQ0tVpdCnnkP/CLKA0SG0jpA6/qjqwfrjezmxxo9IqSo+bZWILyvuuekzl7Ww6wpmLbNWypAWQ+44vJW3/nvyhTyhxfQrxQva71CIEgsIxU+Fd5HZKpM77kuvViM8xdcs0fBhUIjxyp36PYEHyMflJFLAmmPjBXIoztZmbWcHyHJAgMBAAGjXTBbMB0GA1UdDgQWBBSvDZuY4wB0xoOJxo0pIWfdNPSd6jAfBgNVHSMEGDAWgBSvDZuY4wB0xoOJxo0pIWfdNPSd6jAMBgNVHRMBAf8EAjAAMAsGA1UdDwQEAwIFoDANBgkqhkiG9w0BAQsFAAOCAYEAAhWP299m2jIPLea5gUnLfAU29MHjVBaqBunekMsJ6VigElGYsGW5RFcL+/9/6IP3m3bdr8ZpPrm/1rT3LbdkQHZWK2pQebHFe3I+0jd0xja3bD2CuEqDMejq2fHeSFjAL7JRWKlkW6wRgq/Fwhm/UUs0DW207QrjjDOSL+1jC4HgCT/QP07O3XDL6A3ZS1evyUyl9vWKRFo0UJIlB9z1fknFGbUaW661fc2MuRlx7lgY832Dx/6JORrzd+4HPc0k+Gu6+VOvjM9nkinJAes0V2sQlRmddk5AkubuMU06XI4xZiiBTB8gQZgDFSp9QFhGSmmvcvsopFq+F7F793coSYOeFFy0Fk8zy/RAGMm4wx5Kmivv2ODzzPKVf8EjZzynNs6T9y3c0pCYj5i16rynjC1z3UKyFL5CdgsYUWyhf3Dsva2qh8YYywBW07ywB5mXvYAAjV0xEOZqTALjyvJOerTPllsmxpj4D4arqMxUph3upFN+fsBUCDH15QVNtHdw',
          'sp_cert_act_enc',
          'MIIEHzCCAoegAwIBAgIEYJ5fwzANBgkqhkiG9w0BAQsFADAiMSAwHgYDVQQDDBdOZXh0Y2xvdWQgZUlELUxvZ2luIEFwcDAeFw0yMTA1MTQxMTMyMTlaFw0yMzA1MTQxMTMyMTlaMCIxIDAeBgNVBAMMF05leHRjbG91ZCBlSUQtTG9naW4gQXBwMIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAq9fdEOMm4RteGabblIieJViCsKeZk8ub3nvueUOwiY1CvVa0bf999s/ZDzruyoBO6mBkpnTP01TXzbqE1Jme8Qjaf3qN4cQWtHFjF//0eg4i5HTwFNR482OpkBKf6MPaQs6H2K4l34qopmN0qb2Vp4aKe6FrQPl1j85JnqbGhbL39rPDOUd1qmuogJ8uxYx3YyU0cNTpFSeHrI83tKek00b/2oKyQpzpoK7RZizTadX6U82Vc7E978A4Lqv/rFQICxtGLgWYBUuYMrNNag36sPt8ktV/iyE32UcASBZoE1ki5VOCBAqsiJ1paNcQR0v3YBShBoB6nUaDQ2vil3QDiPdUv+8DK721h4jw7yDZ2NBdXhBBMSmypQdML9Rg4DnbpmANuN8KoTTVnQBIDGT1IIs6i6A1utRZDe7jI7BRemWpQg4KSd/FXmcR0F/BIkJz5quHUpC8+Omr6mDVn6hNv86glOPpwiGJA/c0cySfOoen6k9wdi+VAYl6lGWcPNmHAgMBAAGjXTBbMB0GA1UdDgQWBBSh6DCIRAw1QcqvhNodIStLRgQ6fTAfBgNVHSMEGDAWgBSh6DCIRAw1QcqvhNodIStLRgQ6fTAMBgNVHRMBAf8EAjAAMAsGA1UdDwQEAwIFoDANBgkqhkiG9w0BAQsFAAOCAYEAfDo+KBhTnKuw6q/A3QJw3WBb5Fn7wCm74z3XD3kJSYfkiN8PwySKZSonFCqc0d46EE5sqIctBvGd7X/sumLQVl0iuQ4TNXUKUmjfj2jRCVimE4/jBFrHwK56G+TNpmzq2tmFoKmGCDr86rUq6XGgvmVv0vLRzM071xppQuZU65ZAee2G/ve95zrdqxmxRitYsWFrzxpVvQyl9RRyGEIkI62dRYi41dle4lC0b3GUBhUXLo+/Z1fIqhAlm4F4FletcVZYfNhc6GYkyhV5nAVrGjOPUjjy9LqV9/H8w4XCNtu6z+aqT+ZlPmMxAFuGeBhFTUXztxOTcWdgsYQroVrLZdjMLTOhBPj5zg/ansP0G0yNHy02SxBOZJvHqcIzECRGowjxmcz+fw3h8XCqjI6IpRV3pYTQez/PXpnrr99zeLNLuH4wbeBly5TD17hK2rR0BOsa+Hv8gZm/HvtK8fk8Az70W/ZRJD2qgE8ZMjBaBsJjb634leulQ2KW3C5KJqsj',
          'sp_key_act',
          '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',
          'sp_key_act_enc',
          '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',
        ]
      );
      return null;
    },
    async dbClear() {
      console.log('db teardown ...');
      await db.query(
        "DELETE FROM oc_appconfig WHERE appid = 'eidlogin' AND configkey IN ('activated', 'idp_cert_enc', 'idp_cert_sign', 'idp_entity_id', 'idp_ext_tr03130', 'idp_sso_url', 'sp_cert_act', 'sp_cert_act_enc', 'sp_cert_new', 'sp_cert_new_enc', 'sp_cert_old', 'sp_cert_old_enc', 'sp_enforce_enc', 'sp_entity_id', 'sp_key_act', 'sp_key_act_enc', 'sp_key_new', 'sp_key_new_enc', 'sp_key_old', 'sp_key_old_enc');"
      );
      await db.query(
        "DELETE FROM oc_eidlogin_eid_attributes"
      );
      await db.query(
        "DELETE FROM oc_eidlogin_eid_continuedata"
      );
      await db.query(
        "DELETE FROM oc_eidlogin_eid_responsedata"
      );
      await db.query(
        "DELETE FROM oc_eidlogin_eid_users"
      );
      return null;
    }
  })
}
