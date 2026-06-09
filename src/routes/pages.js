const express = require('express');
const router = express.Router();
const { requireAuth } = require('../middleware/auth');

const Student = require('../models/Student');
const Attendance = require('../models/Attendance');
const Institution = require('../models/Institution');
const Skill = require('../models/Skill');
const Project = require('../models/Project');
const Performance = require('../models/Performance');
const { buildPublicQrAssets } = require('../utils/public-qr');

const ALLOWED_PAGES = ['dashboard', 'students', 'history', 'attendance', 'reports', 'institutions', 'settings', 'projects'];

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
        email: req.session.email,
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
                    { $match: { student_count: { $gt: 0 } } },
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
            const StudentGroup = require('../models/StudentGroup');
            const todayDate = new Date();
            todayDate.setHours(0, 0, 0, 0);

            const [students, institutions, skills, pendingStudents, groups] = await Promise.all([
                Student.find({
                    approval_status: 'approved',
                    $or: [
                        { end_date: { $eq: null } },
                        { end_date: { $gte: todayDate } }
                    ]
                }).populate('institution', 'institution_name').populate('skill_of_interest', 'skill_name').populate('student_group').sort({ student_name: 1 }),
                Institution.find({}, 'institution_name').sort({ institution_name: 1 }),
                Skill.find({}, 'skill_name').sort({ skill_name: 1 }),
                Student.find({ approval_status: 'pending' })
                    .populate('institution', 'institution_name')
                    .populate('skill_of_interest', 'skill_name')
                    .populate('student_group')
                    .sort({ created_at: -1 }),
                StudentGroup.find().sort({ year: -1, name: 1 })
            ]);
            viewData = { students, institutions, skills, pendingStudents, groups };
        }

        // ---- HISTORY DATA ----
        if (page === 'history') {
            const todayDate = new Date();
            todayDate.setHours(0, 0, 0, 0);

            const historyStudents = await Student.find({
                approval_status: 'approved',
                end_date: { $lt: todayDate, $ne: null }
            }).populate('institution', 'institution_name').populate('skill_of_interest', 'skill_name').populate('student_group').sort({ end_date: -1 });

            viewData = { students: historyStudents };
        }

        // ---- ATTENDANCE DATA ----
        if (page === 'attendance') {
            const today = new Date().toISOString().split('T')[0];
            const StudentGroup = require('../models/StudentGroup');
            const [students, todayAttendance, summary, activeGroups] = await Promise.all([
                Student.find({ status: 'Active' }).populate('institution', 'institution_name').populate('student_group').sort({ student_name: 1 }),
                Attendance.find({ date: today }).populate('student', 'student_name photo_url'),
                _getTodaySummary(today),
                StudentGroup.find({ status: 'Active' }).sort({ name: 1 })
            ]);

            // Map attendance by student ID for quick lookup in the view
            const attendanceMap = {};
            todayAttendance.forEach(a => {
                if (a.student) attendanceMap[a.student._id.toString()] = a;
            });

            viewData = { students, attendanceMap, today, summary, activeGroups };
        }

        // ---- REPORTS DATA ----
        if (page === 'reports') {
            const studentId = req.query.student_id;
            const allStudents = await Student.find().sort({ student_name: 1 });
            
            let studentData = null;
            let performanceData = null;
            let attendanceData = [];
            let projectCount = 0;
            let studentAttendance = { total_days: 0, present_days: 0, attendance_rate: 0 };

            if (studentId) {
                studentData = await Student.findById(studentId).populate('institution');
                if (studentData) {
                    const latestPerf = await Performance.findOne({ student: studentId })
                        .sort({ evaluation_date: -1 });
                    
                    if (latestPerf) {
                        performanceData = {
                            technical_skill: latestPerf.technical_skill,
                            learning_activity: latestPerf.learning_activity,
                            active_contribution: latestPerf.active_contribution,
                            overall_rating: latestPerf.overall_rating,
                            evaluation_date: latestPerf.evaluation_date,
                            comments: latestPerf.comments
                        };
                    }

                    const escapeRegExp = value => String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const [attRecords, projCount] = await Promise.all([
                        Attendance.find({ student: studentId }).sort({ date: -1 }),
                        Project.countDocuments({ students: new RegExp(escapeRegExp(studentData.student_name), 'i') })
                    ]);
                    
                    attendanceData = attRecords;
                    projectCount = projCount;

                    const totalDays = attRecords.length;
                    const presentDays = attRecords.filter(a => a.status === 'Present' || a.status === 'Late').length;
                    const lateDays = attRecords.filter(a => a.status === 'Late').length;
                    const absentDays = Math.max(0, totalDays - presentDays);
                    const attendanceRate = totalDays > 0 ? Math.round((presentDays / totalDays) * 100) : 0;
                    
                    studentAttendance = {
                        total_days: totalDays,
                        present_days: presentDays,
                        late_days: lateDays,
                        absent_days: absentDays,
                        attendance_rate: attendanceRate
                    };
                }
            }

            viewData = {
                allStudents,
                studentId,
                studentData,
                performanceData,
                attendanceData,
                projectCount,
                studentAttendance
            };
        }

        // ---- INSTITUTIONS DATA ----
        if (page === 'institutions') {
            const institutions = await Institution.find().sort({ institution_name: 1 });
            viewData = { institutions };
        }

        // ---- SETTINGS DATA ----
        if (page === 'settings') {
            const [institutions, skills, totalStudents, totalUsers] = await Promise.all([
                Institution.find().sort({ institution_name: 1 }),
                Skill.find().sort({ skill_name: 1 }),
                Student.countDocuments(),
                // We don't expose user list to the view for security, just count
                Student.countDocuments({ status: 'Active' })
            ]);
            const publicBaseUrl = process.env.PUBLIC_BASE_URL || `${req.protocol}://${req.get('host')}`;
            const { registerUrl, registerQrDataUrl, publicAttendanceLinks } = await buildPublicQrAssets(publicBaseUrl);
            viewData = {
                institutions,
                skills,
                totalStudents,
                activeStudents: totalUsers,
                publicRegisterUrl: registerUrl,
                publicRegisterQrDataUrl: registerQrDataUrl,
                publicAttendanceLinks
            };
        }

        // ---- PROJECTS DATA ----
        if (page === 'projects') {
            const projects = await Project.find().sort({ created_at: -1 });
            viewData = { projects };
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
