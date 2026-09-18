/**
 * progressive-loader.js - Handle multi-step analysis workflow with UX feedback
 */

const ProgressiveLoader = {
    steps: [],
    currentStepIndex: -1,
    onStepStart: null,
    onStepComplete: null,
    onAllComplete: null,

    /**
     * Initialize the loader with steps
     * @param {Array} steps Array of step definitions { id, label, icon }
     */
    init(steps) {
        this.steps = steps;
        this.currentStepIndex = -1;
        this.updateUI();
    },

    /**
     * Start the sequence
     */
    start() {
        this.currentStepIndex = 0;
        this.processNextStep();
    },

    /**
     * Process the next step in the sequence
     */
    processNextStep() {
        if (this.currentStepIndex < this.steps.length) {
            const step = this.steps[this.currentStepIndex];
            
            // Mark step as active in UI
            this.markStepActive(step.id);
            
            if (typeof this.onStepStart === 'function') {
                this.onStepStart(step);
            }

            // The actual execution happens outside which will call completeStep
        } else {
            if (typeof this.onAllComplete === 'function') {
                this.onAllComplete();
            }
        }
    },

    /**
     * Mark a step as complete and move to the next one
     * @param {string} stepId 
     * @param {Object} data Any data returned from the step
     */
    completeStep(stepId, data) {
        this.markStepComplete(stepId);
        
        if (typeof this.onStepComplete === 'function') {
            this.onStepComplete(stepId, data);
        }

        this.currentStepIndex++;
        this.processNextStep();
    },

    /**
     * Mark a step as failed
     * @param {string} stepId 
     * @param {string} error 
     */
    failStep(stepId, error) {
        this.markStepFailed(stepId, error);
        
        // Decide whether to continue or stop
        this.currentStepIndex++;
        this.processNextStep();
    },

    // UI Feedback Helpers
    markStepActive(stepId) {
        $(`.step-item[data-step="${stepId}"]`).addClass('active pulse');
        $(`.step-item[data-step="${stepId}"] .step-status i`).removeClass().addClass('fas fa-spinner fa-spin text-primary');
    },

    markStepComplete(stepId) {
        $(`.step-item[data-step="${stepId}"]`).removeClass('active pulse').addClass('completed');
        $(`.step-item[data-step="${stepId}"] .step-status i`).removeClass().addClass('fas fa-check-circle text-success');
    },

    markStepFailed(stepId, error) {
        $(`.step-item[data-step="${stepId}"]`).removeClass('active pulse').addClass('failed');
        $(`.step-item[data-step="${stepId}"] .step-status i`).removeClass().addClass('fas fa-times-circle text-danger');
        console.error(`Step ${stepId} failed: ${error}`);
    },

    updateUI() {
        // Reset all steps UI
        $('.step-item').removeClass('active pulse completed failed');
        $('.step-status i').removeClass().addClass('fas fa-clock text-muted');
    }
};
