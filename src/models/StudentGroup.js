const mongoose = require('mongoose');

const studentGroupSchema = new mongoose.Schema({
    name: { type: String, required: true, trim: true },
    description: { type: String, default: '' },
    year: { type: Number, default: new Date().getFullYear() },
    status: { type: String, enum: ['Active', 'Archived'], default: 'Active' }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

module.exports = mongoose.model('StudentGroup', studentGroupSchema);
