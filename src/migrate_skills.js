require('dotenv').config();
const mongoose = require('mongoose');
const Student = require('./models/Student');
const Skill = require('./models/Skill');

async function run() {
    try {
        console.log('Connecting to MongoDB...');
        await mongoose.connect(process.env.MONGODB_URI || 'mongodb://127.0.0.1:27017/attendit');
        console.log('Connected.');

        // Get the raw collection to bypass Mongoose schema casting on find()
        const collection = mongoose.connection.collection('students');
        const students = await collection.find({
            skill_of_interest: { $type: 2 } // type 2 is String
        }).toArray();

        console.log(`Found ${students.length} students with string skill_of_interest.`);

        for (const student of students) {
            const skillName = student.skill_of_interest.trim();
            if (!skillName) continue;

            // Find or create skill
            let skill = await Skill.findOne({ skill_name: { $regex: new RegExp(`^${skillName}$`, 'i') } });
            if (!skill) {
                skill = new Skill({ skill_name: skillName });
                await skill.save();
                console.log(`Created new skill: ${skillName}`);
            }

            // Update student
            await collection.updateOne(
                { _id: student._id },
                { $set: { skill_of_interest: skill._id } }
            );
            console.log(`Updated student ${student.student_name} to skill ${skillName}`);
        }

        console.log('Migration completed successfully.');
        process.exit(0);
    } catch (err) {
        console.error('Migration failed:', err);
        process.exit(1);
    }
}

run();
