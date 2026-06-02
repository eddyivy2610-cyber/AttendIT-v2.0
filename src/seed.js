/**
 * AttendIT Seed Script
 * Seeds MongoDB with the existing data from the SQL dump.
 * Run once: node src/seed.js
 */
require('dotenv').config();
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const User = require('./models/User');
const Institution = require('./models/Institution');
const Student = require('./models/Student');

async function seed() {
    await mongoose.connect(process.env.MONGODB_URI);
    console.log('✅ Connected to MongoDB');

    // ─── Clear existing data ───────────────────────────────────────────────────
    await Promise.all([
        User.deleteMany({}),
        Institution.deleteMany({}),
        Student.deleteMany({})
    ]);
    console.log('🗑️  Cleared existing data');

    // ─── Institutions ──────────────────────────────────────────────────────────
    const institutions = await Institution.insertMany([
        { institution_name: 'Ahmadu Bello University', contact_email: 'contact@abu.edu.ng' },
        { institution_name: 'Nuhu Bamalli Polytechnic', contact_email: 'info@nuhubamallipoly.edu.ng' },
        { institution_name: 'AFIT Kaduna', contact_email: 'admin@afit.edu.ng' },
        { institution_name: 'Kaduna State University', contact_email: 'admissions@ksu.edu.ng' },
        { institution_name: 'Yobe State University', contact_email: 'info@yobeuni.edu.ng' },
        { institution_name: 'Federal University Dutse, Jigawa', contact_email: 'fud@edu.ng' },
        { institution_name: 'Base University Abuja', contact_email: 'baseuni@edu.ng' },
        { institution_name: 'Federal University of Education, Zaria', contact_email: 'fue@edu.ng' }
    ]);
    console.log(`✅ Seeded ${institutions.length} institutions`);

    // Map institution names to IDs for student seeding
    const instMap = {};
    institutions.forEach(i => { instMap[i.institution_name] = i._id; });

    // ─── Users ─────────────────────────────────────────────────────────────────
    // Passwords are hashed automatically by the User model pre-save hook
    const users = await User.create([
        {
            user_name: 'ATE School',
            user_email: 'info@ncat.gov.ng',
            password: 'password',        // will be hashed by pre-save
            user_role: 'supervisor',
            is_active: true
        },
        {
            user_name: 'admin',
            user_email: 'admin@ncat.gov.ng',
            password: 'admin123',        // will be hashed by pre-save
            user_role: 'admin',
            is_active: true
        }
    ]);
    console.log(`✅ Seeded ${users.length} users`);
    console.log('   Login: info@ncat.gov.ng / password');
    console.log('   Login: admin@ncat.gov.ng / admin123');

    // ─── Students ──────────────────────────────────────────────────────────────
    const students = await Student.insertMany([
        {
            email: 'john.lucky@student.abu.edu.ng',
            student_name: 'John Lucky',
            period_of_attachment: '6',
            institution: instMap['Ahmadu Bello University'],
            birthday: new Date('2000-10-26'),
            course_of_study: 'Computer Science',
            skill_of_interest: 'Web Design, Graphics Design',
            gender: 'Male',
            join_date: new Date('2025-03-26'),
            end_date: new Date('2025-09-26'),
            supervisor: 'Mr Kabir Muhammad',
            status: 'Active',
            phone: '+2347081066985'
        },
        {
            email: 'zipporah.toma@student.nbp.edu.ng',
            student_name: 'Zipporah Toma',
            period_of_attachment: '6',
            institution: instMap['Nuhu Bamalli Polytechnic'],
            birthday: new Date('1999-05-15'),
            course_of_study: 'Computer Science',
            skill_of_interest: 'Database Management, Networking',
            gender: 'Female',
            join_date: new Date('2025-03-26'),
            end_date: new Date('2025-09-26'),
            supervisor: 'Mr Kabir Muhammad',
            status: 'Active',
            phone: '+2348012345678'
        },
        {
            email: 'shuaibuananu@gmail.com',
            student_name: 'Ananu Awodi',
            period_of_attachment: '3',
            institution: instMap['AFIT Kaduna'],
            course_of_study: 'Information and Communication Engineering',
            skill_of_interest: 'Data Analysis and Hardware',
            gender: 'Female',
            join_date: new Date('2025-09-26'),
            end_date: new Date('2025-12-26'),
            supervisor: 'Kabir Muhammad',
            status: 'Active',
            phone: '+2348132529287'
        },
        {
            email: 'wakawajeremiah@gmail.com',
            student_name: 'Jeremiah Wakawa',
            period_of_attachment: '6',
            institution: instMap['Ahmadu Bello University'],
            course_of_study: 'Computer Science',
            skill_of_interest: 'Web Development',
            gender: 'Male',
            join_date: new Date('2025-09-26'),
            end_date: new Date('2026-03-26'),
            supervisor: 'Kabir Muhammad',
            status: 'Active',
            phone: '+2349037091852'
        },
        {
            email: 'halima@gmail.com',
            student_name: 'Halima Abubakar',
            period_of_attachment: '6',
            institution: instMap['Ahmadu Bello University'],
            course_of_study: 'Computer Science',
            skill_of_interest: 'Web Development',
            gender: 'Female',
            join_date: new Date('2025-10-23'),
            end_date: new Date('2026-04-23'),
            supervisor: 'Kabir Muhammad',
            status: 'Active',
            phone: '+2348132529287'
        },
        {
            email: 'pashirumar003@gmail.com',
            student_name: 'Umar Pashir',
            period_of_attachment: '6',
            institution: instMap['Federal University Dutse, Jigawa'],
            course_of_study: 'Computer Science',
            skill_of_interest: 'Web Development',
            gender: 'Male',
            join_date: new Date('2025-11-10'),
            end_date: new Date('2026-05-10'),
            status: 'Active',
            phone: '+2347048447822'
        }
    ]);
    console.log(`✅ Seeded ${students.length} students`);

    await mongoose.disconnect();
    console.log('\n🎉 Seed complete! You can now start the server: npm run dev');
}

seed().catch(err => {
    console.error('❌ Seed failed:', err.message);
    process.exit(1);
});
