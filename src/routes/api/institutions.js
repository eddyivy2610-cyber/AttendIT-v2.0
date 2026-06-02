const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');

const Institution = require('../../models/Institution');
const Student = require('../../models/Student');

// GET /api/institutions
router.get('/', requireAuth, async (req, res) => {
    try {
        const institutions = await Institution.find().sort({ institution_name: 1 });
        res.json({ success: true, data: institutions });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/institutions
router.post('/', requireAuth, async (req, res) => {
    try {
        const { institution_name, contact_email } = req.body;
        const inst = new Institution({ institution_name, contact_email });
        await inst.save();
        res.json({ success: true, message: 'Institution added', data: inst });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/institutions/:id
router.delete('/:id', requireAuth, async (req, res) => {
    try {
        // Prevent deletion if students are assigned
        const count = await Student.countDocuments({ institution: req.params.id });
        if (count > 0) {
            return res.status(400).json({ success: false, message: `Cannot delete: ${count} student(s) are assigned to this institution.` });
        }
        await Institution.findByIdAndDelete(req.params.id);
        res.json({ success: true, message: 'Institution deleted' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
