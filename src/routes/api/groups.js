const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');
const StudentGroup = require('../../models/StudentGroup');

router.post('/', requireAuth, async (req, res) => {
    try {
        const { name, year } = req.body;
        const group = new StudentGroup({
            name,
            year: year || new Date().getFullYear()
        });
        await group.save();
        res.json({ success: true, data: group });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

router.put('/:id', requireAuth, async (req, res) => {
    try {
        const { name } = req.body;
        if (!name) return res.status(400).json({ success: false, message: 'Name is required' });
        
        const group = await StudentGroup.findByIdAndUpdate(req.params.id, { name }, { new: true });
        if (!group) return res.status(404).json({ success: false, message: 'Group not found' });
        
        res.json({ success: true, data: group });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
