const mongoose = require('mongoose');

const performanceSchema = new mongoose.Schema({
    student: { type: mongoose.Schema.Types.ObjectId, ref: 'Student', required: true },
    evaluated_by: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    evaluation_date: { type: Date, default: Date.now },
    technical_skill: { type: Number, required: true, min: 0, max: 100 },
    learning_activity: { type: Number, required: true, min: 0, max: 100 },
    active_contribution: { type: Number, required: true, min: 0, max: 100 },
    comments: { type: String, default: '' }
}, { timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' } });

// Virtual field for overall rating (average of the three metrics)
performanceSchema.virtual('overall_rating').get(function() {
    return Math.round((this.technical_skill + this.learning_activity + this.active_contribution) / 3);
});

module.exports = mongoose.model('Performance', performanceSchema);
