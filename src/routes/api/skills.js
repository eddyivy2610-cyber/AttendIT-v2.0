const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');

const Skill = require('../../models/Skill');
const Student = require('../../models/Student');

// GET /api/skills
router.get('/', requireAuth, async (req, res) => {
    try {
        const skills = await Skill.find().sort({ skill_name: 1 });
        res.json({ success: true, data: skills });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/skills
router.post('/', requireAuth, async (req, res) => {
    try {
        const { skill_name, description } = req.body;
        const skill = new Skill({ skill_name, description });
        await skill.save();
        res.json({ success: true, message: 'Skill added', data: skill });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/skills/:id
router.delete('/:id', requireAuth, async (req, res) => {
    try {
        // Prevent deletion if students are assigned to this skill
        const count = await Student.countDocuments({ skill_of_interest: req.params.id });
        if (count > 0) {
            return res.status(400).json({ success: false, message: `Cannot delete: ${count} student(s) are assigned to this skill.` });
        }
        await Skill.findByIdAndDelete(req.params.id);
        res.json({ success: true, message: 'Skill deleted' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
