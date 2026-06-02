require('dotenv').config();
const mongoose = require('mongoose');

const User = require('./models/User');

async function seedAdmin() {
    await mongoose.connect(process.env.MONGODB_URI);
    console.log('Connected to MongoDB');

    const adminUser = {
        user_name: 'Admin User',
        user_email: 'admin@example.com',
        password: 'admin123',
        user_role: 'admin',
        is_active: true,
        last_login: null
    };

    await User.deleteOne({ user_email: adminUser.user_email });
    await User.create(adminUser);

    console.log(`Seeded admin user: ${adminUser.user_email} / ${adminUser.password}`);
    await mongoose.disconnect();
}

seedAdmin().catch(async err => {
    console.error('Seed failed:', err.message);
    try {
        await mongoose.disconnect();
    } catch (disconnectError) {
        // ignore disconnect errors
    }
    process.exit(1);
});
