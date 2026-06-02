const mongoose = require('mongoose');

const institutionSchema = new mongoose.Schema({
    institution_name: { type: String, required: true },
    institution_logo: { type: String, default: null },
    contact_email: { type: String, default: null }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

module.exports = mongoose.model('Institution', institutionSchema);
