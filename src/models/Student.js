const mongoose = require('mongoose');

const studentSchema = new mongoose.Schema({
    email: { type: String, required: true, unique: true, lowercase: true, trim: true },
    student_name: { type: String, required: true, trim: true },
    period_of_attachment: { type: String, default: null },
    institution: { type: mongoose.Schema.Types.ObjectId, ref: 'Institution', default: null },
    birthday: { type: Date, default: null },
    course_of_study: { type: String, default: null },
    skill_of_interest: { type: String, default: null },
    gender: { type: String, enum: ['Male', 'Female', 'Other'], default: null },
    join_date: { type: Date, default: null },
    end_date: { type: Date, default: null },
    supervisor: { type: String, default: null },
    status: { type: String, enum: ['Active', 'Inactive'], default: 'Active' },
    approval_status: { type: String, enum: ['pending', 'approved', 'rejected'], default: 'approved' },
    approved_at: { type: Date, default: null },
    phone: { type: String, default: null },
    photo_url: { type: String, default: null }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

module.exports = mongoose.model('Student', studentSchema);
