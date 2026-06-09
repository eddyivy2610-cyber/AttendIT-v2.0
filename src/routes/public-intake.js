const express = require('express');
const router = express.Router();

const Student = require('../models/Student');
const Institution = require('../models/Institution');
const Skill = require('../models/Skill');
const StudentGroup = require('../models/StudentGroup');
const { normalizePhoneNumber } = require('../utils/phone');

function isValidToken(req) {
    const token = process.env.PUBLIC_REGISTER_TOKEN || process.env.PUBLIC_STUDENT_FORM_TOKEN || 'change-me-public-register-token';
    return req.params.token === token;
}

function addMonths(date, months) {
    const result = new Date(date);
    result.setMonth(result.getMonth() + Number(months || 0));
    return result;
}

function renderRegisterPage(res, req, institutions, skills, groups, options = {}) {
    res.render('public/student-intake', {
        institutions,
        skills,
        groups,
        success: options.success || false,
        error: options.error || null,
        formData: options.formData || {},
        publicFormAction: options.publicFormAction || `/register/${req.params.token}`
    });
}

router.get(['/register/:token', '/join/:token'], async (req, res) => {
    if (!isValidToken(req)) {
        return res.status(404).render('error', { message: 'Public student form not found.' });
    }

    try {
        const institutions = await Institution.find({}, 'institution_name').sort({ institution_name: 1 });
        const skills = await Skill.find({}, 'skill_name').sort({ skill_name: 1 });
        const groups = await StudentGroup.find({ status: 'Active' }).sort({ name: 1 });
        renderRegisterPage(res, req, institutions, skills, groups, {
            success: req.query.success === '1',
            publicFormAction: `/register/${req.params.token}`
        });
    } catch (err) {
        res.status(500).render('error', { message: err.message });
    }
});

router.post(['/register/:token', '/join/:token'], async (req, res) => {
    if (!isValidToken(req)) {
        return res.status(404).render('error', { message: 'Public student form not found.' });
    }

    try {
        const institutions = await Institution.find({}, 'institution_name').sort({ institution_name: 1 });
        const skills = await Skill.find({}, 'skill_name').sort({ skill_name: 1 });
        const {
            student_name,
            email,
            phone,
            gender,
            institution_id,
            course_of_study,
            period_of_attachment,
            skill_id,
            student_group
        } = req.body;

        if (!student_name || !email || !phone || !gender || !institution_id || !course_of_study) {
            const groups = await StudentGroup.find({ status: 'Active' }).sort({ name: 1 });
            return res.status(400).render('public/student-intake', {
                institutions,
                skills,
                groups,
                success: false,
                error: 'Please fill in all required fields.',
                formData: req.body,
                publicFormAction: `/register/${req.params.token}`
            });
        }

        let institution = await Institution.findById(institution_id);
        if (!institution) {
            const groups = await StudentGroup.find({ status: 'Active' }).sort({ name: 1 });
            return res.status(400).render('public/student-intake', {
                institutions,
                skills,
                groups,
                success: false,
                error: 'Selected institution is invalid.',
                formData: req.body,
                publicFormAction: `/register/${req.params.token}`
            });
        }
        
        let skillRef = null;
        if (skill_id) {
            const sk = await Skill.findById(skill_id);
            if (sk) skillRef = sk._id;
        }

        const joinDate = new Date();
        const normalizedPhone = normalizePhoneNumber(phone);
        
        const existingStudent = await Student.findOne({
            $or: [
                { email: email.trim().toLowerCase() },
                { phone: normalizedPhone }
            ]
        });

        if (existingStudent) {
            const groups = await StudentGroup.find({ status: 'Active' }).sort({ name: 1 });
            let errorMsg = 'A student with this email already exists.';
            if (existingStudent.phone === normalizedPhone) {
                errorMsg = 'A student with this phone number already exists.';
            }
            if (existingStudent.email === email.trim().toLowerCase() && existingStudent.phone === normalizedPhone) {
                errorMsg = 'A student with this email and phone number already exists.';
            }
            return res.status(400).render('public/student-intake', {
                institutions,
                skills,
                groups,
                success: false,
                error: errorMsg,
                formData: req.body,
                publicFormAction: `/register/${req.params.token}`
            });
        }

        const newStudent = new Student({
            email: email.trim().toLowerCase(),
            student_name: student_name.trim(),
            phone: normalizedPhone,
            gender,
            institution: institution._id,
            course_of_study: course_of_study.trim(),
            period_of_attachment: period_of_attachment || null,
            skill_of_interest: skillRef,
            student_group: student_group || null,
            join_date: joinDate,
            end_date: period_of_attachment ? addMonths(joinDate, period_of_attachment) : null,
            status: 'Inactive',
            approval_status: 'pending',
            approved_at: null
        });

        await newStudent.save();
        return res.redirect(`/register/${req.params.token}?success=1`);
    } catch (err) {
        const institutions = await Institution.find({}, 'institution_name').sort({ institution_name: 1 });
        const skills = await Skill.find({}, 'skill_name').sort({ skill_name: 1 });
        const groups = await StudentGroup.find({ status: 'Active' }).sort({ name: 1 });
        if (err.code === 11000) {
            return res.status(400).render('public/student-intake', {
                institutions,
                skills,
                groups,
                success: false,
                error: 'A student with this email already exists.',
                formData: req.body,
                publicFormAction: `/register/${req.params.token}`
            });
        }

        return res.status(500).render('public/student-intake', {
            institutions,
            skills,
            groups,
            success: false,
            error: err.message,
            formData: req.body,
            publicFormAction: `/register/${req.params.token}`
        });
    }
});

module.exports = router;
