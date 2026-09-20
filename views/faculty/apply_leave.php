<?php
// views/faculty/apply_leave.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Apply for Leave | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-2xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-off" class="w-6 h-6 text-emerald-500"></i> Apply for Leave
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Submit a leave request for approval by the administration.</p>
            </div>
            <a href="<?= $base ?>/views/faculty/my_leaves.php" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                <i data-lucide="list" class="w-4 h-4"></i> View My Leaves
            </a>
        </div>

        <!-- Leave Application Form -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Gradient top accent -->
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600"></div>
            
            <form action="<?= $base ?>/controllers/process_leave.php" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="apply">

                <!-- Leave Type -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        <i data-lucide="tag" class="w-4 h-4 inline-block mr-1 text-emerald-500"></i> Leave Type
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="leave-type-option group">
                            <input type="radio" name="leave_type" value="SICK" class="sr-only peer" required>
                            <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer transition-all hover:border-rose-300 dark:hover:border-rose-700 peer-checked:border-rose-500 peer-checked:bg-rose-50 dark:peer-checked:bg-rose-900/20 peer-checked:shadow-md">
                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                                    <i data-lucide="thermometer" class="w-5 h-5 text-rose-500"></i>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Sick Leave</span>
                            </div>
                        </label>
                        <label class="leave-type-option group">
                            <input type="radio" name="leave_type" value="CASUAL" class="sr-only peer" checked>
                            <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer transition-all hover:border-amber-300 dark:hover:border-amber-700 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:shadow-md">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                    <i data-lucide="sun" class="w-5 h-5 text-amber-500"></i>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Casual Leave</span>
                            </div>
                        </label>
                        <label class="leave-type-option group">
                            <input type="radio" name="leave_type" value="OTHER" class="sr-only peer">
                            <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer transition-all hover:border-emerald-300 dark:hover:border-emerald-700 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 peer-checked:shadow-md">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                    <i data-lucide="file-text" class="w-5 h-5 text-emerald-500"></i>
                                </div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Other</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Custom Subject (Hidden by default, shown for OTHER) -->
                <div id="custom-subject-container" class="hidden">
                    <label for="custom_subject" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        <i data-lucide="edit-3" class="w-4 h-4 inline-block mr-1 text-emerald-500"></i> Subject / Reason Type
                    </label>
                    <input type="text" id="custom_subject" name="custom_subject" minlength="3" maxlength="255"
                        placeholder="E.g., Conference, Workshop"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm placeholder:text-slate-400">
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1 text-emerald-500"></i> Start Date
                        </label>
                        <input type="date" id="start_date" name="start_date" required
                            min="<?= date('Y-m-d') ?>"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1 text-emerald-500"></i> End Date
                        </label>
                        <input type="date" id="end_date" name="end_date" required
                            min="<?= date('Y-m-d') ?>"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium">
                    </div>
                </div>

                <!-- Duration Preview -->
                <div id="duration-preview" class="hidden">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30">
                        <i data-lucide="clock" class="w-5 h-5 text-emerald-500"></i>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                            Duration: <span id="duration-days" class="text-emerald-600 dark:text-emerald-400">0</span> day(s)
                        </span>
                    </div>
                </div>

                <!-- Reason -->
                <div>
                    <label for="reason" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        <i data-lucide="message-square" class="w-4 h-4 inline-block mr-1 text-purple-500"></i> Reason for Leave
                    </label>
                    <textarea id="reason" name="reason" rows="4" required minlength="10"
                        placeholder="Please provide a detailed reason for your leave request..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm resize-none placeholder:text-slate-400"></textarea>
                    <p class="text-xs text-slate-400 mt-1.5">Minimum 10 characters required</p>
                </div>

                <!-- Supporting Documents -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        <i data-lucide="paperclip" class="w-4 h-4 inline-block mr-1 text-slate-500"></i> Supporting Documents (Optional)
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 dark:border-slate-600 border-dashed rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors relative group">
                        <div class="space-y-1 text-center">
                            <i data-lucide="upload-cloud" class="mx-auto h-12 w-12 text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                            <div class="flex text-sm text-slate-600 dark:text-slate-400 justify-center">
                                <label for="supporting_docs" class="relative cursor-pointer bg-white dark:bg-slate-700 rounded-md font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-emerald-500">
                                    <span>Upload files</span>
                                    <input id="supporting_docs" name="supporting_docs[]" type="file" class="sr-only" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-500">Up to 3 files. PDF, JPG, PNG, DOC up to 5MB each.</p>
                        </div>
                    </div>
                    <div id="file-list" class="mt-3 space-y-2 empty:hidden"></div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="<?= $base ?>/views/faculty/my_leaves.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all hover:-translate-y-0.5">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const preview = document.getElementById('duration-preview');
    const daysSpan = document.getElementById('duration-days');

    function updateDuration() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            if (diff > 0) {
                daysSpan.textContent = diff;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        } else {
            preview.classList.add('hidden');
        }
    }

    startDate.addEventListener('change', () => {
        if (startDate.value) {
            endDate.min = startDate.value;
            if (endDate.value && endDate.value < startDate.value) {
                endDate.value = startDate.value;
            }
        }
        updateDuration();
    });

    endDate.addEventListener('change', updateDuration);

    // Handle Leave Type Selection for Custom Subject
    const leaveTypeRadios = document.querySelectorAll('input[name="leave_type"]');
    const customSubjectContainer = document.getElementById('custom-subject-container');
    const customSubjectInput = document.getElementById('custom_subject');

    leaveTypeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'OTHER') {
                customSubjectContainer.classList.remove('hidden');
                customSubjectInput.setAttribute('required', 'required');
            } else {
                customSubjectContainer.classList.add('hidden');
                customSubjectInput.removeAttribute('required');
                customSubjectInput.value = ''; // clear when hidden
            }
        });
    });

    // Handle File Upload Preview
    const fileInput = document.getElementById('supporting_docs');
    const fileList = document.getElementById('file-list');

    fileInput.addEventListener('change', function() {
        fileList.innerHTML = ''; // clear previous list
        
        if (this.files.length > 3) {
            Swal.fire('Too many files', 'You can only upload a maximum of 3 documents.', 'warning');
            this.value = ''; // clear input
            return;
        }

        Array.from(this.files).forEach((file, index) => {
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File too large', `The file ${file.name} exceeds the 5MB limit.`, 'error');
                this.value = ''; // clear input
                fileList.innerHTML = '';
                return;
            }

            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-sm';
            
            // Format size
            let size = file.size;
            let sizeStr = size + ' B';
            if (size > 1024 * 1024) sizeStr = (size / (1024 * 1024)).toFixed(2) + ' MB';
            else if (size > 1024) sizeStr = (size / 1024).toFixed(1) + ' KB';

            fileItem.innerHTML = `
                <div class="flex items-center gap-2 truncate">
                    <i data-lucide="file" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                    <span class="truncate text-slate-700 dark:text-slate-200 font-medium">${file.name}</span>
                </div>
                <span class="text-xs text-slate-500 whitespace-nowrap ml-4">${sizeStr}</span>
            `;
            fileList.appendChild(fileItem);
        });
        
        // Re-initialize lucide icons for the newly added elements
        if (typeof lucide !== 'undefined') {
            lucide.createIcons({ root: fileList });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
