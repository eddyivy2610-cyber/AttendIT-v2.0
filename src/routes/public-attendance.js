const express = require('express');
const router = express.Router();

const Attendance = require('../models/Attendance');
const Student = require('../models/Student');
const { normalizePhoneNumber } = require('../utils/phone');
const { WEEKDAYS, attendanceTokenFor } = require('../utils/public-qr');

function todayStr() {
    return new Date().toISOString().split('T')[0];
}

function isValidRouteToken(req) {
    const weekday = String(req.params.weekday || '').toLowerCase();
    if (!WEEKDAYS.includes(weekday)) return false;
    return req.params.token === attendanceTokenFor(weekday);
}

function isLate() {
    const now = new Date();
    return now.getHours() > 8 || (now.getHours() === 8 && now.getMinutes() > 30);
}

function renderPage(res, req, options = {}) {
    const weekday = String(req.params.weekday || '').toLowerCase();
    res.render('public/attendance', {
        weekday,
        title: `${weekday.charAt(0).toUpperCase() + weekday.slice(1)} Attendance`,
        formAction: `/attendance/${weekday}/${req.params.token}`,
        lookupAction: `/attendance/${weekday}/${req.params.token}`,
        success: options.success || false,
        error: options.error || null,
        message: options.message || null,
        phone: options.phone || '',
        student: options.student || null,
        attendanceState: options.attendanceState || null
    });
}

async function findStudentByPhone(phone) {
    const normalizedPhone = normalizePhoneNumber(phone);
    if (!normalizedPhone) return null;

    const students = await Student.find({
        phone: { $ne: null },
        status: 'Active',
        approval_status: 'approved'
    }, 'student_name phone status approval_status');

    return students.find(student => normalizePhoneNumber(student.phone) === normalizedPhone) || null;
}

async function getAttendanceState(studentId, date) {
    const record = await Attendance.findOne({ student: studentId, date });
    if (!record) {
        return { label: 'Not signed in yet', key: 'not_signed_in', record: null };
    }

    if (record.departure_time) {
        return { label: 'Signed out', key: 'signed_out', record };
    }

    if (record.arrival_time) {
        return { label: 'Signed in', key: 'signed_in', record };
    }

    return { label: 'Pending', key: 'pending', record };
}

router.get('/attendance/:weekday/:token', async (req, res) => {
    if (!isValidRouteToken(req)) {
        return res.status(404).render('error', { message: 'Public attendance form not found.' });
    }

    return renderPage(res, req);
});

router.post('/attendance/:weekday/:token', async (req, res) => {
    if (!isValidRouteToken(req)) {
        return res.status(404).render('error', { message: 'Public attendance form not found.' });
    }

    try {
        const { phone, action } = req.body;
        const trimmedAction = String(action || '').toLowerCase();
        const student = await findStudentByPhone(phone);

        if (!student) {
            return renderPage(res, req, {
                error: 'No approved student matches that phone number.',
                phone: phone || ''
            });
        }

        const date = todayStr();
        const now = new Date();
        const existing = await Attendance.findOne({ student: student._id, date });

        if (trimmedAction === 'lookup') {
            const attendanceState = await getAttendanceState(student._id, date);
            return renderPage(res, req, {
                success: true,
                message: `Matched ${student.student_name}.`,
                phone: phone || '',
                student,
                attendanceState
            });
        }

        if (trimmedAction === 'signin') {
            if (existing?.arrival_time && !existing.departure_time) {
                const attendanceState = await getAttendanceState(student._id, date);
                return renderPage(res, req, {
                    error: 'You are already signed in for today.',
                    phone: phone || '',
                    student,
                    attendanceState
                });
            }

            if (existing?.departure_time) {
                const attendanceState = await getAttendanceState(student._id, date);
                return renderPage(res, req, {
                    error: 'You already signed out for today.',
                    phone: phone || '',
                    student,
                    attendanceState
                });
            }

            const status = isLate() ? 'Late' : 'Present';
            const record = await Attendance.findOneAndUpdate(
                { student: student._id, date },
                {
                    $setOnInsert: { student: student._id, date, notes: '' },
                    $set: { arrival_time: now, status, departure_time: null }
                },
                { upsert: true, new: true }
            );

            const attendanceState = await getAttendanceState(student._id, date);
            return renderPage(res, req, {
                success: true,
                message: `Signed in successfully as ${status}.`,
                phone: phone || '',
                student,
                attendanceState
            });
        }

        if (trimmedAction === 'signout') {
            if (!existing || !existing.arrival_time) {
                const attendanceState = await getAttendanceState(student._id, date);
                return renderPage(res, req, {
                    error: 'You must sign in before signing out.',
                    phone: phone || '',
                    student,
                    attendanceState
                });
            }

            if (existing.departure_time) {
                const attendanceState = await getAttendanceState(student._id, date);
                return renderPage(res, req, {
                    error: 'You are already signed out for today.',
                    phone: phone || '',
                    student,
                    attendanceState
                });
            }

            await Attendance.findOneAndUpdate(
                { student: student._id, date },
                { $set: { departure_time: now } },
                { new: true }
            );

            const attendanceState = await getAttendanceState(student._id, date);
            return renderPage(res, req, {
                success: true,
                message: 'Signed out successfully.',
                phone: phone || '',
                student,
                attendanceState
            });
        }

        const attendanceState = await getAttendanceState(student._id, todayStr());
        return renderPage(res, req, {
            error: 'Choose either Sign In or Sign Out.',
            phone: phone || '',
            student,
            attendanceState
        });
    } catch (err) {
        return renderPage(res, req, {
            error: err.message,
            phone: req.body.phone || ''
        });
    }
});

router.get('/attendance', (req, res) => {
    res.redirect('/login');
});

module.exports = { router, attendanceTokenFor };
