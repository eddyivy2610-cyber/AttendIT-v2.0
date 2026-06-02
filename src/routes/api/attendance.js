const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');

const Attendance = require('../../models/Attendance');
const Student = require('../../models/Student');

// Helper: today's date string
function todayStr() {
    return new Date().toISOString().split('T')[0];
}

// Helper: is this time after 08:30?
function isLate() {
    const now = new Date();
    return now.getHours() > 8 || (now.getHours() === 8 && now.getMinutes() > 30);
}

// GET /api/attendance — today's attendance list
router.get('/', requireAuth, async (req, res) => {
    try {
        const date = req.query.date || todayStr();
        const records = await Attendance.find({ date })
            .populate('student', 'student_name gender photo_url period_of_attachment');
        res.json({ success: true, data: records });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// GET /api/attendance/summary — today's summary counts
router.get('/summary', requireAuth, async (req, res) => {
    try {
        const date = req.query.date || todayStr();
        const [total, records] = await Promise.all([
            Student.countDocuments({ status: 'Active' }),
            Attendance.find({ date })
        ]);

        const presentCount = records.filter(r => r.status === 'Present').length;
        const lateCount = records.filter(r => r.status === 'Late').length;
        const absentCount = Math.max(0, total - presentCount - lateCount);
        const rate = total > 0 ? Math.round(((presentCount + lateCount) / total) * 100) : 0;

        res.json({ success: true, data: { total, presentCount, lateCount, absentCount, rate } });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// GET /api/attendance/monthly — monthly summary for reports
router.get('/monthly', requireAuth, async (req, res) => {
    try {
        const year = parseInt(req.query.year) || new Date().getFullYear();
        const month = parseInt(req.query.month) || new Date().getMonth() + 1;

        const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
        const endOfMonth = new Date(year, month, 0).getDate();
        const endDate = `${year}-${String(month).padStart(2, '0')}-${endOfMonth}`;

        const [records, totalStudents] = await Promise.all([
            Attendance.find({ date: { $gte: startDate, $lte: endDate } }),
            Student.countDocuments({ status: 'Active' })
        ]);

        // Group by date
        const byDate = {};
        records.forEach(r => {
            if (!byDate[r.date]) byDate[r.date] = { present: 0, late: 0, absent: 0 };
            if (r.status === 'Present') byDate[r.date].present++;
            else if (r.status === 'Late') byDate[r.date].late++;
            else byDate[r.date].absent++;
        });

        const summary = Object.entries(byDate).map(([date, counts]) => ({
            date,
            present_count: counts.present,
            late_count: counts.late,
            absent_count: Math.max(0, totalStudents - counts.present - counts.late),
            total_students: totalStudents
        })).sort((a, b) => a.date.localeCompare(b.date));

        res.json({ success: true, data: summary });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// GET /api/attendance/student/:id — history for one student
router.get('/student/:id', requireAuth, async (req, res) => {
    try {
        const records = await Attendance.find({ student: req.params.id })
            .sort({ date: -1 }).limit(60);
        res.json({ success: true, data: records });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/attendance/signin — sign a student in
router.post('/signin', requireAuth, async (req, res) => {
    try {
        const { student_id } = req.body;
        const today = todayStr();
        const now = new Date();
        const status = isLate() ? 'Late' : 'Present';

        const record = await Attendance.findOneAndUpdate(
            { student: student_id, date: today },
            {
                $setOnInsert: { student: student_id, date: today, notes: '' },
                $set: { arrival_time: now, status }
            },
            { upsert: true, new: true }
        );

        res.json({ success: true, message: `Signed in as ${status}`, data: record });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/attendance/signout — sign a student out
router.post('/signout', requireAuth, async (req, res) => {
    try {
        const { student_id } = req.body;
        const today = todayStr();
        const now = new Date();

        const record = await Attendance.findOneAndUpdate(
            { student: student_id, date: today, departure_time: null },
            { $set: { departure_time: now } },
            { new: true }
        );

        if (!record) {
            return res.status(400).json({ success: false, message: 'No sign-in found for today, or already signed out.' });
        }

        res.json({ success: true, message: 'Signed out successfully', data: record });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/attendance/mark — manually mark attendance (admin)
router.post('/mark', requireAuth, async (req, res) => {
    try {
        const { student_id, date, status, notes } = req.body;
        const record = await Attendance.findOneAndUpdate(
            { student: student_id, date: date || todayStr() },
            {
                $setOnInsert: { student: student_id, date: date || todayStr() },
                $set: { status, notes: notes || '', marked_by: req.session.userId }
            },
            { upsert: true, new: true }
        );
        res.json({ success: true, message: 'Attendance marked', data: record });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/attendance/reset — reset today's/specified date's attendance
router.delete('/reset', requireAuth, async (req, res) => {
    try {
        const date = req.query.date || todayStr();
        const result = await Attendance.deleteMany({ date });
        res.json({ success: true, message: `Reset today's attendance (${result.deletedCount} records deleted)` });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;

