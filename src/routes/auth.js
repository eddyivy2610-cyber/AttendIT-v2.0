const express = require('express');
const router = express.Router();
const User = require('../models/User');
const { redirectIfLoggedIn } = require('../middleware/auth');

// GET /login — show login page
router.get('/login', redirectIfLoggedIn, (req, res) => {
    res.render('login', {
        error: req.session.error || null,
        prefillEmail: req.session.prefillEmail || ''
    });
});

// POST /login — handle login form submission
router.post('/login', redirectIfLoggedIn, async (req, res) => {
    const { user_email, password } = req.body;

    // Clear any stale error
    req.session.error = null;

    if (!user_email || !password) {
        req.session.error = 'Please enter both email and password.';
        req.session.prefillEmail = user_email || '';
        return res.redirect('/login');
    }

    try {
        const user = await User.findOne({ user_email: user_email.toLowerCase().trim() });

        if (!user) {
            req.session.error = 'Invalid email or password.';
            req.session.prefillEmail = user_email;
            return res.redirect('/login');
        }

        if (!user.is_active) {
            req.session.error = 'Your account has been deactivated.';
            return res.redirect('/login');
        }

        const passwordMatch = await user.verifyPassword(password);
        if (!passwordMatch) {
            req.session.error = 'Invalid email or password.';
            req.session.prefillEmail = user_email;
            return res.redirect('/login');
        }

        // Set session — equivalent to PHP $_SESSION
        req.session.userId = user._id.toString();
        req.session.username = user.user_name;
        req.session.email = user.user_email;
        req.session.role = user.user_role;
        req.session.error = null;
        req.session.prefillEmail = '';

        // Update last login
        await User.findByIdAndUpdate(user._id, { last_login: new Date() });

        return res.redirect('/');

    } catch (err) {
        console.error('Login error:', err);
        req.session.error = 'Server error. Please try again.';
        return res.redirect('/login');
    }
});

// GET /logout
router.get('/logout', (req, res) => {
    req.session.destroy(() => {
        res.redirect('/login');
    });
});

module.exports = router;
