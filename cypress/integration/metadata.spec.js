/**
 * Test the SAML Metadata endpoint.
 */
describe('Test the eID Template SP SAML metadata endpoint', () => {
  before(() => {
    cy.task('dbClear')
  });
  it('Check 404 response if sp is not configured', () => {
    cy.request({url: '/apps/eidlogin/saml/meta',failOnStatusCode: false}).then((resp) => {
      expect(resp.status).to.eq(404);
    });
  });
  it('Check XML response if sp is configured', () => {
    cy.task('dbSeed')
    cy.request('/apps/eidlogin/saml/meta').then((resp) => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('text/xml;charset=UTF-8');
      const xml = Cypress.$.parseXML(resp.body);
      const entityDescriptor = xml.getElementsByTagName('md:EntityDescriptor')[0];
      expect(entityDescriptor.getAttribute('entityID')).to.eq(Cypress.config().baseUrl);

      const signature = xml.getElementsByTagName('ds:Signature')[0];
      expect(signature).to.be.not.null;
      const signatureValue = signature.querySelectorAll('SignatureValue');
      expect(signatureValue).to.be.not.null;

      const spSSODescriptor = xml.getElementsByTagName('md:SPSSODescriptor')[0];
      expect(spSSODescriptor.getAttribute('WantAssertionsSigned')).to.eq('true');
      expect(spSSODescriptor.getAttribute('AuthnRequestsSigned')).to.eq('true');

      const keyDescriptor = xml.getElementsByTagName('md:KeyDescriptor')[0];
      expect(keyDescriptor.getAttribute('use')).to.eq('signing');
      const certs = keyDescriptor.querySelectorAll('X509Certificate');
      expect(certs.length).to.eq(1);

      const acs = xml.getElementsByTagName('md:AssertionConsumerService')[0];
      expect(acs.getAttribute('Location')).to.eq('https://nextcloud22.p396.de/apps/eidlogin/saml/acs');
      expect(acs.getAttribute('Binding')).to.eq('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');

      const nameIDFormat = xml.getElementsByTagName('md:NameIDFormat')[0];
      expect(nameIDFormat).to.be.not.null;
    });
  });
  after(() => {
    cy.task('dbClear')
  });
});
