const mongoose = require('mongoose');

const attendanceSchema = new mongoose.Schema({
    student: { type: mongoose.Schema.Types.ObjectId, ref: 'Student', required: true },
    date: { type: String, required: true },          // 'YYYY-MM-DD' string for easy daily queries
    arrival_time: { type: Date, default: null },
    departure_time: { type: Date, default: null },
    status: { type: String, enum: ['Present', 'Absent', 'Late', 'Pending'], default: 'Pending' },
    notes: { type: String, default: '' },
    marked_by: { type: mongoose.Schema.Types.ObjectId, ref: 'User', default: null }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

// Compound unique index — one record per student per day
attendanceSchema.index({ student: 1, date: 1 }, { unique: true });

module.exports = mongoose.model('Attendance', attendanceSchema);
