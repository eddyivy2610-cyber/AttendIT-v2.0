const express = require('express');
const router = express.Router();
const { requireAuth } = require('../../middleware/auth');
const Project = require('../../models/Project');

// GET /api/projects — list all projects
router.get('/', requireAuth, async (req, res) => {
    try {
        const projects = await Project.find().sort({ created_at: -1 });
        res.json({ success: true, data: projects });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/projects — create a project
router.post('/', requireAuth, async (req, res) => {
    try {
        const { project_name, students, supervisor, start_date, due_date, technology_stack, repository_url, description } = req.body;
        if (!project_name || !students || !supervisor || !start_date || !due_date || !technology_stack) {
            return res.status(400).json({ success: false, message: 'All required fields must be filled.' });
        }

        const project = new Project({
            project_name,
            students,
            supervisor,
            start_date,
            due_date,
            technology_stack,
            repository_url,
            description,
            created_by: req.session.userId
        });

        await project.save();
        res.json({ success: true, message: 'Project created successfully!', data: project });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// PUT /api/projects/:id — update a project
router.put('/:id', requireAuth, async (req, res) => {
    try {
        const { project_name, students, supervisor, start_date, due_date, technology_stack, repository_url, description, status } = req.body;
        
        const project = await Project.findById(req.params.id);
        if (!project) {
            return res.status(404).json({ success: false, message: 'Project not found.' });
        }

        if (project_name) project.project_name = project_name;
        if (students) project.students = students;
        if (supervisor) project.supervisor = supervisor;
        if (start_date) project.start_date = start_date;
        if (due_date) project.due_date = due_date;
        if (technology_stack) project.technology_stack = technology_stack;
        if (repository_url !== undefined) project.repository_url = repository_url;
        if (description !== undefined) project.description = description;
        if (status) project.status = status;

        await project.save();
        res.json({ success: true, message: 'Project updated successfully!', data: project });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// POST /api/projects/:id/complete — mark as completed
router.post('/:id/complete', requireAuth, async (req, res) => {
    try {
        const project = await Project.findByIdAndUpdate(
            req.params.id,
            { status: 'Completed' },
            { new: true }
        );
        if (!project) {
            return res.status(404).json({ success: false, message: 'Project not found.' });
        }
        res.json({ success: true, message: 'Project marked as completed!', data: project });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// DELETE /api/projects/:id — delete a project
router.delete('/:id', requireAuth, async (req, res) => {
    try {
        const project = await Project.findByIdAndDelete(req.params.id);
        if (!project) {
            return res.status(404).json({ success: false, message: 'Project not found.' });
        }
        res.json({ success: true, message: 'Project deleted successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;
