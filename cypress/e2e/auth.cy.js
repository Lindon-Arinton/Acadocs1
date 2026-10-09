// Public pages: nothing here needs a real account, and nothing changes data
// (the reset-password checks use a made-up email, so no code is ever sent).
// The app's redirects keep the index.php prefix (/index.php/login), so paths
// are matched on their ending.

describe('Login', () => {
  it('shows the sign-in form', () => {
    cy.visit('/login');
    cy.get('#loginForm').should('be.visible');
    cy.get('input[name="email"]').should('be.visible');
    cy.get('input[name="password"]').should('be.visible');
  });

  it('rejects a wrong password', () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type('nobody@example.invalid');
    cy.get('input[name="password"]').type('wrong-password');
    cy.get('#loginSubmit').click();
    cy.location('pathname').should('match', /\/login$/);
    cy.get('.alert-danger').should('be.visible');
  });

  it('sends signed-out visitors to the login page', () => {
    cy.visit('/dashboard');
    cy.location('pathname').should('match', /\/login$/);
  });
});

describe('Reset password', () => {
  beforeEach(() => {
    cy.visit('/forgot-password');
    cy.get('input[name="email"]').type('nobody@example.invalid').closest('form').submit();
    cy.location('pathname').should('match', /\/reset-password$/);
  });

  it('asks for the code first, with no password fields yet', () => {
    cy.contains('button', 'Verify Code').should('be.visible');
    cy.get('input[name="password"]').should('not.exist');
    cy.get('#newPasswordModal').should('not.exist');
  });

  it('rejects a wrong code', () => {
    cy.get('input[name="code"]').type('123456');
    cy.contains('button', 'Verify Code').click();
    cy.get('.alert-danger').should('contain', 'invalid or has expired');
    cy.get('#newPasswordModal').should('not.exist');
  });
});
