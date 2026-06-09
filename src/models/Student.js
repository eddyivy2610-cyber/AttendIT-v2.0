const mongoose = require('mongoose');

function toTitleCase(str) {
    if (!str) return str;
    return str.replace(
        /\w\S*/g,
        function(txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        }
    );
}

const studentSchema = new mongoose.Schema({
    email: { type: String, required: true, unique: true, lowercase: true, trim: true },
    student_name: { type: String, required: true, trim: true, set: toTitleCase },
    period_of_attachment: { type: String, default: null },
    institution: { type: mongoose.Schema.Types.ObjectId, ref: 'Institution', default: null },
    birthday: { type: Date, default: null },
    course_of_study: { type: String, default: null },
    skill_of_interest: { type: mongoose.Schema.Types.ObjectId, ref: 'Skill', default: null },
    gender: { type: String, enum: ['Male', 'Female', 'Other'], default: null },
    student_group: { type: mongoose.Schema.Types.ObjectId, ref: 'StudentGroup', default: null },
    join_date: { type: Date, default: null },
    end_date: { type: Date, default: null },
    status: { type: String, enum: ['Active', 'Inactive'], default: 'Active' },
    approval_status: { type: String, enum: ['pending', 'approved', 'rejected'], default: 'approved' },
    approved_at: { type: Date, default: null },
    phone: { type: String, default: null, unique: true, sparse: true },
    photo_url: { type: String, default: null }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

module.exports = mongoose.model('Student', studentSchema);
