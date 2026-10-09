// Signed-in checks per role. Each block skips itself until that role's account
// is set in cypress.env.json, so the suite still runs on a fresh machine.
// Paths are matched on their ending: redirects keep the index.php prefix.

/** before() hook that skips the whole block when the role has no account configured. */
const requireAccount = (role) => function () {
  cy.credentials(role).then((creds) => {
    if (!creds) this.skip();
  });
};

describe('Principal (admin)', () => {
  before(requireAccount('admin'));
  beforeEach(() => cy.login('admin'));

  it('lands on the school dashboard', () => {
    cy.visit('/dashboard');
    cy.contains('Welcome back,').should('be.visible');
    cy.contains('Enrollment Breakdown').should('exist');
  });

  it('asks before logging out, and Cancel keeps you signed in', () => {
    cy.visit('/dashboard');
    cy.get('a[href$="/logout"]').click({ force: true });
    cy.contains('.swal2-popup', 'Log out?').should('be.visible');
    cy.contains('.swal2-popup button', 'Cancel').click();
    cy.location('pathname').should('match', /\/dashboard$/);
  });
});

describe('Teacher', () => {
  before(requireAccount('teacher'));
  beforeEach(() => cy.login('teacher'));

  it('lands on the teacher dashboard', () => {
    cy.visit('/');
    cy.location('pathname').should('match', /\/teacher-dashboard$/);
    cy.contains('My MPS Performance').should('be.visible');
  });

  it('cannot open User Management', () => {
    cy.visit('/users');
    cy.location('pathname').should('not.match', /\/users$/);
  });

  it('opens Messages on "Select a conversation"', () => {
    cy.visit('/chat');
    cy.contains('Select a conversation').should('be.visible');
  });
});
