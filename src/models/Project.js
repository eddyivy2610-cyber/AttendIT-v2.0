const mongoose = require('mongoose');

const projectSchema = new mongoose.Schema({
    project_name: { type: String, required: true },
    students: { type: String, required: true },
    supervisor: { type: String, required: true },
    start_date: { type: Date, required: true },
    due_date: { type: Date, required: true },
    technology_stack: { type: String, required: true },
    repository_url: { type: String, default: '' },
    description: { type: String, default: '' },
    status: { type: String, default: 'In Progress', enum: ['In Progress', 'Completed'] },
    created_by: { type: mongoose.Schema.Types.ObjectId, ref: 'User' }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

module.exports = mongoose.model('Project', projectSchema);
