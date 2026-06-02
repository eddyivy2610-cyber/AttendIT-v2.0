require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const session = require('express-session');
const { MongoStore } = require('connect-mongo');
const path = require('path');

const app = express();

// ─── Database Connection ──────────────────────────────────────────────────────
mongoose.connect(process.env.MONGODB_URI)
    .then(() => console.log('✅ MongoDB connected'))
    .catch(err => { console.error('❌ MongoDB connection error:', err.message); process.exit(1); });

// ─── View Engine ─────────────────────────────────────────────────────────────
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// ─── Static Files (your existing assets/css, assets/js, images) ──────────────
app.use(express.static(path.join(__dirname)));

// ─── Body Parsers ─────────────────────────────────────────────────────────────
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ─── Sessions (replaces PHP sessions) ────────────────────────────────────────
app.use(session({
    secret: process.env.SESSION_SECRET || 'attendit-secret-change-me',
    resave: false,
    saveUninitialized: false,
    store: new MongoStore({
        mongoUrl: process.env.MONGODB_URI,
        collectionName: 'sessions',
        ttl: 60 * 60 * 8  // 8 hours
    }),
    cookie: {
        secure: process.env.NODE_ENV === 'production',
        httpOnly: true,
        maxAge: 1000 * 60 * 60 * 8  // 8 hours
    }
}));

// ─── Routes ──────────────────────────────────────────────────────────────────
const authRoutes = require('./src/routes/auth');
const publicIntakeRoutes = require('./src/routes/public-intake');
const { router: publicAttendanceRoutes } = require('./src/routes/public-attendance');
const qrPrintRoutes = require('./src/routes/qr-print');
const pageRoutes = require('./src/routes/pages');
const studentApiRoutes = require('./src/routes/api/students');
const attendanceApiRoutes = require('./src/routes/api/attendance');
const institutionApiRoutes = require('./src/routes/api/institutions');
const settingsApiRoutes = require('./src/routes/api/settings');
const projectApiRoutes = require('./src/routes/api/projects');

app.use('/', authRoutes);
app.use('/', publicIntakeRoutes);
app.use('/', publicAttendanceRoutes);
app.use('/', qrPrintRoutes);
app.use('/', pageRoutes);
app.use('/api/students', studentApiRoutes);
app.use('/api/attendance', attendanceApiRoutes);
app.use('/api/institutions', institutionApiRoutes);
app.use('/api/settings', settingsApiRoutes);
app.use('/api/projects', projectApiRoutes);

// ─── 404 Handler ─────────────────────────────────────────────────────────────
app.use((req, res) => {
    res.status(404).render('error', { message: `Page not found: ${req.path}` });
});

// ─── Global Error Handler ─────────────────────────────────────────────────────
app.use((err, req, res, next) => {
    console.error('Unhandled error:', err);
    res.status(500).render('error', { message: err.message || 'Internal server error' });
});

// ─── Start Server ─────────────────────────────────────────────────────────────
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 AttendIT running at http://localhost:${PORT}`);
});
