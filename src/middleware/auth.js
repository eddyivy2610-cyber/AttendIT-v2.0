/**
 * Auth middleware — replaces PHP isLoggedIn() / requireAuth()
 */
function requireAuth(req, res, next) {
    if (req.session && req.session.userId) {
        return next();
    }
    return res.redirect('/login');
}

function redirectIfLoggedIn(req, res, next) {
    if (req.session && req.session.userId) {
        return res.redirect('/');
    }
    next();
}

module.exports = { requireAuth, redirectIfLoggedIn };
