const mongoose = require('mongoose');

const skillSchema = new mongoose.Schema({
    skill_name: { type: String, required: true, trim: true },
    description: { type: String, trim: true, default: '' }
}, { timestamps: true });

module.exports = mongoose.model('Skill', skillSchema);
