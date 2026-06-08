const express = require('express');
const router = express.Router();
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { requireAuth } = require('../../middleware/auth');

const Student = require('../../models/Student');
const Institution = require('../../models/Institution');
const { normalizePhoneNumber } = require('../../utils/phone');

// Multer config for passport photo uploads
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const uploadDir = path.join(__dirname, '../../..', 'uploads', 'passports');
        if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        const ext = path.extname(file.originalname);
        cb(null, `passport_${Date.now()}${ext}`);
    }
});
const upload = multer({ storage, limits: { fileSize: 5 * 1024 * 1024 } }); // 5MB limit

// GET /api/students — list all students with institution name
router.get('/', requireAuth, async (req, res) => {
    try {
        const students = await Student.find()
            .populate('institution', 'institution_name')
            .sort({ student_name: 1 });

        res.json({ success: true, data: students });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// GET /api/students/:id — single student
router.get('/:id', requireAuth, async (req, res) => {
    try {
        const student = await Student.findById(req.params.id)
            .populate('institution', 'institution_name');
        if (!student) return res.status(404).json({ success: false, message: 'Student not found' });
        res.json({ success: true, data: student });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/students — create student
router.post('/', requireAuth, upload.single('photo'), async (req, res) => {
    try {
        const {
            email, student_name, period_of_attachment, institution_id,
            birthday, course_of_study, skill_of_interest, gender, days_of_week_batch,
            join_date, end_date, phone, status
        } = req.body;

        // Find institution by its MongoDB _id
        let institutionRef = null;
        if (institution_id) {
            const inst = await Institution.findById(institution_id);
            if (inst) institutionRef = inst._id;
        }

        const newStudent = new Student({
            email: email.trim().toLowerCase(),
            student_name: student_name.trim(),
            period_of_attachment: period_of_attachment || null,
            institution: institutionRef,
            birthday: birthday || null,
            course_of_study: course_of_study || null,
            skill_of_interest: skill_of_interest || null,
            gender: gender || null,
            days_of_week_batch: days_of_week_batch || null,
            join_date: join_date || null,
            end_date: end_date || null,
            phone: phone ? normalizePhoneNumber(phone) : null,
            status: status || 'Active',
            approval_status: 'approved',
            approved_at: new Date(),
            photo_url: req.file ? `uploads/passports/${req.file.filename}` : null
        });

        await newStudent.save();
        res.json({ success: true, message: 'Student registered successfully', data: newStudent });
    } catch (err) {
        if (err.code === 11000) {
            return res.status(400).json({ success: false, message: 'A student with this email already exists.' });
        }
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/students/:id/approve — approve a pending student
router.post('/:id/approve', requireAuth, async (req, res) => {
    try {
        const student = await Student.findByIdAndUpdate(
            req.params.id,
            {
                status: 'Active',
                approval_status: 'approved',
                approved_at: new Date()
            },
            { new: true, runValidators: true }
        ).populate('institution', 'institution_name');

        if (!student) return res.status(404).json({ success: false, message: 'Student not found' });

        res.json({ success: true, message: 'Student approved successfully', data: student });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// PUT /api/students/:id — update student
router.put('/:id', requireAuth, upload.single('photo'), async (req, res) => {
    try {
        const updates = { ...req.body };

        // Resolve institution reference
        if (updates.institution_id) {
            const inst = await Institution.findById(updates.institution_id);
            updates.institution = inst ? inst._id : null;
            delete updates.institution_id;
        }

        // If a new photo was uploaded
        if (req.file) {
            updates.photo_url = `uploads/passports/${req.file.filename}`;
        }

        // Normalize email
        if (updates.email) updates.email = updates.email.trim().toLowerCase();
        if (updates.phone) updates.phone = normalizePhoneNumber(updates.phone);

        const student = await Student.findByIdAndUpdate(req.params.id, updates, {
            new: true, runValidators: true
        }).populate('institution', 'institution_name');

        if (!student) return res.status(404).json({ success: false, message: 'Student not found' });

        res.json({ success: true, message: 'Student updated successfully', data: student });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/students/:id
router.delete('/:id', requireAuth, async (req, res) => {
    try {
        const student = await Student.findByIdAndDelete(req.params.id);
        if (!student) return res.status(404).json({ success: false, message: 'Student not found' });
        res.json({ success: true, message: 'Student deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/students/:id/performance — add performance evaluation
router.post('/:id/performance', requireAuth, async (req, res) => {
    try {
        const { technical_skill, learning_activity, active_contribution, comments } = req.body;
        const Performance = require('../../models/Performance');
        
        const newPerformance = new Performance({
            student: req.params.id,
            evaluated_by: req.session.userId,
            technical_skill: parseInt(technical_skill) || 0,
            learning_activity: parseInt(learning_activity) || 0,
            active_contribution: parseInt(active_contribution) || 0,
            comments: comments ? comments.trim() : ''
        });

        await newPerformance.save();
        res.json({ success: true, message: 'Performance rating added successfully!', data: newPerformance });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
