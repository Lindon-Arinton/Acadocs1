// Custom commands for the ACADOCS end-to-end tests.
//
// Accounts come from cypress.env.json (git-ignored; copy cypress.env.example.json):
//   { "ADMIN_EMAIL": "...", "ADMIN_PASSWORD": "...", "TEACHER_EMAIL": "...", ... }
// Cypress 16 reads these with cy.env() (Cypress.env() was removed).

/** Yields { email, password } for a role ('admin', 'teacher', …), or null when not configured. */
Cypress.Commands.add('credentials', (role) => {
  const emailKey = `${role.toUpperCase()}_EMAIL`;
  const passwordKey = `${role.toUpperCase()}_PASSWORD`;

  return cy.env([emailKey, passwordKey], { log: false }).then((env) => (
    env[emailKey] && env[passwordKey] ? { email: env[emailKey], password: env[passwordKey] } : null
  ));
});

/**
 * Logs in through the real login form. cy.session() caches the session per role,
 * so later tests in the run skip the form.
 */
Cypress.Commands.add('login', (role) => {
  cy.credentials(role).then((creds) => {
    if (!creds) {
      throw new Error(`Set ${role.toUpperCase()}_EMAIL and ${role.toUpperCase()}_PASSWORD in cypress.env.json`);
    }

    cy.session([role, creds.email], () => {
      cy.visit('/login');
      cy.get('input[name="email"]').clear().type(creds.email);
      cy.get('input[name="password"]').type(creds.password, { log: false });
      cy.get('#loginSubmit').click();
      // Redirects may carry the index.php prefix (/index.php/dashboard).
      cy.location('pathname').should('not.match', /\/login$/);
    });
  });
});
