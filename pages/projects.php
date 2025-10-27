<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error-message">Please log in to view projects.</div>';
    return;
}

include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../model/projects.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $project = new Project($db);
    
    // Get all projects
    $projects = $project->read();
} catch (Exception $e) {
    error_log("Error loading projects: " . $e->getMessage());
    $projects = [];
}
?>

<div class="projects-container">
    <div class="projects-header">
        <div class="projects-actions">
            <div class="search-box">
                <ion-icon name="search-outline"></ion-icon>
                <input type="text" id="projectSearch" placeholder="Search projects...">
            </div>
            <button class="btn-primary" onclick="showAddProjectModal()">
                <ion-icon name="add-outline"></ion-icon>
                Add Project
            </button>
        </div>
    </div>

    <div class="table-container">
        <table class="projects-table">
            <thead>
                <tr>
                    <th>Project Title</th>
                    <th>Student(s)</th>
                    <th>Supervised By</th>
                    <th>Date Started</th>
                    <th>Due Date</th>
                    <th>Tech Stack</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($projects)): ?>
                    <?php while ($project_item = $projects->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="<?= $project_item['status'] === 'Completed' ? 'completed' : '' ?>">
                            <td>
                                <div class="project-title"><?= htmlspecialchars($project_item['project_name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($project_item['students']) ?></td>
                            <td><?= htmlspecialchars($project_item['supervisor']) ?></td>
                            <td><?= date('M j, Y', strtotime($project_item['start_date'])) ?></td>
                            <td>
                                <div class="due-date <?= $project_item['status'] === 'Completed' ? 'completed' : (strtotime($project_item['due_date']) < time() ? 'overdue' : '') ?>">
                                    <?= date('M j, Y', strtotime($project_item['due_date'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="tech-stack"><?= htmlspecialchars($project_item['technology_stack']) ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($project_item['status']) ?>">
                                    <?= $project_item['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon view-source" onclick="viewProjectSource(<?= $project_item['project_id'] ?>)" title="View Source">
                                        <ion-icon name="code-slash-outline"></ion-icon>
                                    </button>
                                    <?php if ($project_item['status'] !== 'Completed'): ?>
                                        <button class="btn-icon complete" onclick="markComplete(<?= $project_item['project_id'] ?>)" title="Mark Complete">
                                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-icon edit" onclick="editProject(<?= $project_item['project_id'] ?>)" title="Edit">
                                        <ion-icon name="create-outline"></ion-icon>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteProject(<?= $project_item['project_id'] ?>)" title="Delete">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Sample projects data -->
                    <tr>
                        <td>
                            <div class="project-title">Attendit, Web Application</div>
                        </td>
                        <td>Faith Adeyefa</td>
                        <td>Engr. Timothy</td>
                        <td>Aug 1, 2025</td>
                        <td>
                            <div class="due-date completed">October, 2025</div>
                        </td>
                        <td>
                            <div class="tech-stack">HTML, CSS, JavaScript, PHP</div>
                        </td>
                        <td>
                            <span class="status-badge status-completed">Completed</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon view-source" onclick="viewProjectSource(1)" title="View Source">
                                    <ion-icon name="code-slash-outline"></ion-icon>
                                </button>
                                <button class="btn-icon edit" onclick="editProject(1)" title="Edit">
                                    <ion-icon name="create-outline"></ion-icon>
                                </button>
                                <button class="btn-icon delete" onclick="deleteProject(1)" title="Delete">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Project Modal (same as before, but updated fields) -->
<div id="addProjectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Project</h3>
            <span class="close" onclick="closeAddProjectModal()">&times;</span>
        </div>
        <form id="projectForm" class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Project Title *</label>
                    <input type="text" name="project_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Student(s) *</label>
                    <input type="text" name="students" placeholder="Sarah Chen, Mike Johnson" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Supervisor *</label>
                    <input type="text" name="supervisor" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Technology Stack *</label>
                    <input type="text" name="technology_stack" placeholder="React, Node.js, MongoDB" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Repository URL</label>
                    <input type="url" name="repository_url" placeholder="https://github.com/username/repo">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Project Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the project..."></textarea>
                </div>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeAddProjectModal()">Cancel</button>
            <button class="btn-primary" onclick="saveProject()">Save Project</button>
        </div>
    </div>
</div>
