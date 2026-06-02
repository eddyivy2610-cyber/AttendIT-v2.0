const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');
const User = require('../../models/User');

// POST /api/settings/profile — update profile info
router.post('/profile', requireAuth, async (req, res) => {
    try {
        const { username, email } = req.body;
        if (!username || !email) {
            return res.status(400).json({ success: false, message: 'Username and email are required.' });
        }

        const user = await User.findById(req.session.userId);
        if (!user) {
            return res.status(404).json({ success: false, message: 'User not found.' });
        }

        // Check if email already taken
        if (email.toLowerCase().trim() !== user.user_email) {
            const existing = await User.findOne({ user_email: email.toLowerCase().trim() });
            if (existing) {
                return res.status(400).json({ success: false, message: 'Email address is already in use.' });
            }
        }

        user.user_name = username;
        user.user_email = email.toLowerCase().trim();
        await user.save();

        // Update session
        req.session.username = user.user_name;
        req.session.email = user.user_email;

        res.json({ success: true, message: 'Profile updated successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/settings/password — change password
router.post('/password', requireAuth, async (req, res) => {
    try {
        const { current_password, new_password } = req.body;
        if (!current_password || !new_password) {
            return res.status(400).json({ success: false, message: 'Current and new passwords are required.' });
        }

        const user = await User.findById(req.session.userId);
        if (!user) {
            return res.status(404).json({ success: false, message: 'User not found.' });
        }

        const isMatch = await user.verifyPassword(current_password);
        if (!isMatch) {
            return res.status(400).json({ success: false, message: 'Incorrect current password.' });
        }

        user.password = new_password; // schema pre('save') hashes it!
        await user.save();

        res.json({ success: true, message: 'Password updated successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
