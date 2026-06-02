const express = require('express');
const router = express.Router();
const { requireAuth } = require('../middleware/auth');

const Student = require('../models/Student');
const Attendance = require('../models/Attendance');
const Institution = require('../models/Institution');
const { buildPublicQrAssets } = require('../utils/public-qr');

const ALLOWED_PAGES = ['dashboard', 'students', 'attendance', 'reports', 'settings', 'projects'];

// GET /api/dashboard-data — returns JSON for real-time dashboard updates
router.get('/api/dashboard-data', requireAuth, async (req, res) => {
    try {
        const today = new Date().toISOString().split('T')[0];
        const [totalStudents, todayAttendance] = await Promise.all([
            Student.countDocuments({ status: 'Active' }),
            Attendance.find({ date: today })
        ]);

        const present = todayAttendance.filter(a => a.status === 'Present' || a.status === 'Late').length;
        const attendanceRate = totalStudents > 0 ? Math.round((present / totalStudents) * 100) : 0;

        res.json({
            totalStudents,
            attendanceRate,
            activeStudents: present
        });
    } catch (err) {
        console.error('Error in /api/dashboard-data:', err);
        res.status(500).json({ error: 'Failed to fetch dashboard data' });
    }
});

// GET / — main app shell, loads correct page content
router.get('/', requireAuth, async (req, res) => {
    let page = req.query.page || 'dashboard';
    if (!ALLOWED_PAGES.includes(page)) page = 'dashboard';

    const currentUser = {
        userId: req.session.userId,
        username: req.session.username,
        role: req.session.role
    };

    const currentDate = new Date().toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    let viewData = {};

    try {
        // ---- DASHBOARD DATA ----
        if (page === 'dashboard') {
            const today = new Date().toISOString().split('T')[0];

            const [totalStudents, recentStudents, todayAttendance, institutions] = await Promise.all([
                Student.countDocuments({ status: 'Active' }),
                Student.find({ status: 'Active' }, 'student_name gender period_of_attachment created_at')
                    .sort({ created_at: -1 }).limit(5),
                Attendance.find({ date: today }),
                Institution.aggregate([
                    {
                        $lookup: {
                            from: 'students',
                            localField: '_id',
                            foreignField: 'institution',
                            as: 'students'
                        }
                    },
                    {
                        $project: {
                            institution_name: 1,
                            student_count: {
                                $size: {
                                    $filter: {
                                        input: '$students',
                                        as: 's',
                                        cond: { $eq: ['$$s.status', 'Active'] }
                                    }
                                }
                            }
                        }
                    },
                    { $sort: { student_count: -1 } }
                ])
            ]);

            const present = todayAttendance.filter(a => a.status === 'Present' || a.status === 'Late').length;
            const attendanceRate = totalStudents > 0 ? Math.round((present / totalStudents) * 100) : 0;

            viewData = {
                totalStudents,
                recentStudents,
                activeStudents: present,
                attendanceRate,
                institutions
            };
        }

        // ---- STUDENTS DATA ----
        if (page === 'students') {
            const [students, institutions, pendingStudents] = await Promise.all([
                Student.find().populate('institution', 'institution_name').sort({ student_name: 1 }),
                Institution.find({}, 'institution_name').sort({ institution_name: 1 }),
                Student.find({ approval_status: 'pending' })
                    .populate('institution', 'institution_name')
                    .sort({ created_at: -1 })
            ]);
            viewData = { students, institutions, pendingStudents };
        }

        // ---- ATTENDANCE DATA ----
        if (page === 'attendance') {
            const today = new Date().toISOString().split('T')[0];
            const [students, todayAttendance, summary] = await Promise.all([
                Student.find({ status: 'Active' }, 'student_name photo_url gender period_of_attachment').populate('institution', 'institution_name').sort({ student_name: 1 }),
                Attendance.find({ date: today }).populate('student', 'student_name photo_url'),
                _getTodaySummary(today)
            ]);

            // Map attendance by student ID for quick lookup in the view
            const attendanceMap = {};
            todayAttendance.forEach(a => {
                if (a.student) attendanceMap[a.student._id.toString()] = a;
            });

            viewData = { students, attendanceMap, today, summary };
        }

        // ---- REPORTS DATA ----
        if (page === 'reports') {
            const students = await Student.find({ status: 'Active' }, 'student_name').sort({ student_name: 1 });
            viewData = { students };
        }

        // ---- SETTINGS DATA ----
        if (page === 'settings') {
            const [institutions, totalStudents, totalUsers] = await Promise.all([
                Institution.find().sort({ institution_name: 1 }),
                Student.countDocuments(),
                // We don't expose user list to the view for security, just count
                Student.countDocuments({ status: 'Active' })
            ]);
            const publicBaseUrl = process.env.PUBLIC_BASE_URL || `${req.protocol}://${req.get('host')}`;
            const { registerUrl, registerQrDataUrl, publicAttendanceLinks } = await buildPublicQrAssets(publicBaseUrl);
            viewData = {
                institutions,
                totalStudents,
                activeStudents: totalUsers,
                publicRegisterUrl: registerUrl,
                publicRegisterQrDataUrl: registerQrDataUrl,
                publicAttendanceLinks
            };
        }

        // ---- PROJECTS DATA ----
        if (page === 'projects') {
            viewData = {};
        }

        res.render('index', {
            page,
            currentUser,
            currentDate,
            ...viewData
        });

    } catch (err) {
        console.error(`Error loading page '${page}':`, err);
        res.status(500).render('error', { message: 'Failed to load page: ' + err.message });
    }
});

// Helper: calculate today's attendance summary
async function _getTodaySummary(today) {
    const [totalStudents, todayRecords] = await Promise.all([
        Student.countDocuments({ status: 'Active' }),
        Attendance.find({ date: today })
    ]);

    const presentCount = todayRecords.filter(a => a.status === 'Present').length;
    const lateCount = todayRecords.filter(a => a.status === 'Late').length;
    const absentCount = totalStudents - presentCount - lateCount;
    const rate = totalStudents > 0 ? Math.round(((presentCount + lateCount) / totalStudents) * 100) : 0;

    return { totalStudents, presentCount, lateCount, absentCount: Math.max(0, absentCount), rate };
}

module.exports = router;
