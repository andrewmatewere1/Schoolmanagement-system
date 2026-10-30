<?php
if (!empty($_SESSION['admin_id']) && !is_super_admin()) {
  $srmsNavModules=[
    'create-class.php'=>'student_class','manage-classes.php'=>'student_class',
    'add-students.php'=>'students','manage-students.php'=>'students','edit-student.php'=>'students','export-students.php'=>'students',
    'create-subject.php'=>'subjects','manage-subjects.php'=>'subjects','edit-subject.php'=>'subjects','add-subjectcombination.php'=>'subjects','manage-subjectcombination.php'=>'subjects',
    'add-teacher.php'=>'teachers','manage-teachers.php'=>'teachers','edit-teacher.php'=>'teachers','teacher-assignments.php'=>'teachers','teacher-workload.php'=>'teachers',
    'add-result.php'=>'results','manage-results.php'=>'results','edit-result.php'=>'results','find-result.php'=>'results','result-approvals.php'=>'results','result-history.php'=>'results','report-card.php'=>'results','manage-grading.php'=>'results','class-performance.php'=>'results',
    'timetable.php'=>'timetable_exams','student-lifecycle.php'=>'student_lifecycle','finance-accounting.php'=>'finance_accounting','fee-configurations.php'=>'finance_accounting','fee-reports.php'=>'finance_accounting','fee-student-workspace.php'=>'finance_accounting','manage-fees.php'=>'finance_accounting',
    'add-notice.php'=>'notices','edit-notice.php'=>'notices','manage-notices.php'=>'notices','notice-details.php'=>'notices',
    'teacher-leave-management.php'=>'management','teacher-leave-settings.php'=>'management','audit-logs.php'=>'management','notifications.php'=>'management','academic-periods.php'=>'management','academic-archive.php'=>'management','guardian-accounts.php'=>'management'
  ];
  echo '<style>';
  foreach($srmsNavModules as $srmsNavFile=>$srmsNavModule) if(!srms_admin_has_permission($dbh,(int)$_SESSION['admin_id'],$srmsNavModule,false)) echo 'a[href="'.htmlspecialchars($srmsNavFile,ENT_QUOTES,'UTF-8').'"]{display:none!important;}';
  echo '</style><script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".left-sidebar li.has-children").forEach(function(li){var links=Array.from(li.querySelectorAll(".child-nav a"));if(links.length&&links.every(function(a){return getComputedStyle(a).display==="none";}))li.style.display="none";});});</script>';
}
?>
<div class="left-sidebar bg-black-300 box-shadow " id="srms-left-sidebar">
                        <div class="sidebar-content">
                            <div class="user-info closed">
                                <img src="profile-photo.php?type=admin&id=<?php echo (int)($_SESSION['admin_id']??0);?>" alt="Profile photo" class="img-circle profile-img" onerror="this.style.display='none'">
                                <h6 class="title"><?php echo e($_SESSION['alogin']??'Admin');?></h6>
                                <small class="info"><?php echo e(current_admin_role()==='super_admin'?'Super Administrator':'Administrator');?></small>
                            </div>
                            <!-- /.user-info -->

                            <div class="sidebar-nav">
                                <ul class="side-nav color-gray">
                                    <li class="nav-header">
                                        <span class="">Main Category</span>
                                    </li>
                                    <li>
                                        <a href="dashboard.php"><i class="fa fa-dashboard"></i> <span>Dashboard</span> </a>
                                     
                                    </li>
                                    <?php if (function_exists('is_super_admin') && is_super_admin()): ?>
                                    <li><a href="super-admin.php"><i class="fa fa-shield"></i> <span>Super Admin Control Centre</span></a></li>
                                    <?php endif; ?>

                                    <li><a href="teacher-leave-management.php"><i class="fa fa-calendar"></i> <span>Teacher Leave Management</span></a></li>
                                    <li class="nav-header">
                                        <span class="">Appearance</span>
                                    </li>
                                    <li class="has-children">
                                        <a href="#"><i class="fa fa-file-text"></i> <span>Student Classes</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="create-class.php"><i class="fa fa-bars"></i> <span>Create Class</span></a></li>
                                            <li><a href="manage-classes.php"><i class="fa fa fa-server"></i> <span>Manage Classes</span></a></li>
                                           
                                        </ul>
                                    </li>
  <li class="has-children">
                                        <a href="#"><i class="fa fa-file-text"></i> <span>Subjects</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="create-subject.php"><i class="fa fa-bars"></i> <span>Create Subject</span></a></li>
                                            <li><a href="manage-subjects.php"><i class="fa fa fa-server"></i> <span>Manage Subjects</span></a></li>
                                           <li><a href="add-subjectcombination.php"><i class="fa fa-newspaper-o"></i> <span>Add Subject Combination </span></a></li>
                                           <li><a href="manage-subjectcombination.php"><i class="fa fa-newspaper-o"></i> <span>Manage Subject Combination</span></a></li>
                                        </ul>
                                    </li>
   <li class="has-children">
                                        <a href="#"><i class="fa fa-users"></i> <span>Students</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="add-students.php"><i class="fa fa-bars"></i> <span>Add Students</span></a></li>
                                            <li><a href="manage-students.php"><i class="fa fa fa-server"></i> <span>Manage Students</span></a></li>
                                           
                                        </ul>
                                    </li>
<li class="has-children">
                                        <a href="#"><i class="fa fa-user"></i> <span>Teachers</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="add-teacher.php"><i class="fa fa-plus"></i> <span>Add Teacher</span></a></li>
                                            <li><a href="manage-teachers.php"><i class="fa fa-users"></i> <span>Manage Teachers</span></a></li>
                                            <li><a href="teacher-assignments.php"><i class="fa fa-link"></i> <span>Teacher Assignments</span></a></li>
                                            <li><a href="result-approvals.php"><i class="fa fa-check-square-o"></i> <span>Result Approvals</span></a></li>
                                        </ul>
                                    </li>
<li class="has-children">
                                        <a href="#"><i class="fa fa-info-circle"></i> <span>Result</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="add-result.php"><i class="fa fa-bars"></i> <span>Add Result</span></a></li>
                                            <li><a href="academic-periods.php"><i class="fa fa-calendar"></i> Academic Periods</a></li><li><a href="result-approvals.php"><i class="fa fa-check-square-o"></i> Result Approvals</a></li><li><a href="result-history.php"><i class="fa fa-history"></i> Result History</a></li>
<li><a href="manage-results.php"><i class="fa fa fa-server"></i> <span>Manage Result</span></a></li>
                                           
                                        </ul>        </li>


<li class="has-children"><a href="#"><i class="fa fa-calendar"></i> <span>Timetable & Exams</span> <i class="fa fa-angle-right arrow"></i></a><ul class="child-nav"><li><a href="timetable.php">Timetable & Examination Scheduling</a></li></ul></li>
<li class="has-children"><a href="#"><i class="fa fa-exchange"></i> <span>Student Lifecycle</span> <i class="fa fa-angle-right arrow"></i></a><ul class="child-nav"><li><a href="student-lifecycle.php">Promotion / Transfer / Graduation</a></li></ul></li>
<li class="has-children"><a href="#"><i class="fa fa-money"></i> <span>Finance & Accounting</span> <i class="fa fa-angle-right arrow"></i></a><ul class="child-nav"><li><a href="finance-accounting.php">Income, Expenditure & Budgets</a></li></ul></li>
<li class="has-children">
                                        <a href="#"><i class="fa fa-bell"></i> <span>Notices</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="add-notice.php"><i class="fa fa-bars"></i> <span>Add Notice</span></a></li>
                                            <li><a href="manage-notices.php"><i class="fa fa fa-server"></i> <span>Manage Notices</span></a></li>
                                           
                                        </ul>        </li>



                                        <li class="has-children">
                                        <a href="#"><i class="fa fa-bar-chart"></i> <span>Management</span> <i class="fa fa-angle-right arrow"></i></a>
                                        <ul class="child-nav">
                                            <li><a href="class-performance.php"><i class="fa fa-line-chart"></i> <span>Class Performance</span></a></li>
                                            <li><a href="manage-grading.php"><i class="fa fa-graduation-cap"></i> <span>Grading System</span></a></li>
                                            <li><a href="manage-fees.php"><i class="fa fa-money"></i> <span>Fees Management</span></a></li>
                                            <li><a href="fee-configurations.php"><i class="fa fa-sliders"></i> <span>Fee Configurations</span></a></li>
                                            <li><a href="fee-student-workspace.php"><i class="fa fa-credit-card"></i> <span>Student Payment Workspace</span></a></li>
                                            <li><a href="fee-reports.php"><i class="fa fa-file-excel-o"></i> <span>Financial Reports</span></a></li>
                                            <li><a href="academic-archive.php"><i class="fa fa-history"></i> <span>Academic Archive</span></a></li>
                                            <li><a href="notification-delivery.php"><i class="fa fa-envelope"></i> <span>Guardian Notifications</span></a></li>
                                            <li><a href="teacher-workload.php"><i class="fa fa-tasks"></i> <span>Teacher Workload</span></a></li>
                                            <li><a href="notifications.php"><i class="fa fa-bell"></i> <span>Notifications</span></a></li>
                                            <li><a href="audit-logs.php"><i class="fa fa-history"></i> <span>Audit Logs</span></a></li>
                                        </ul>
                                    </li>

                                    <li><a href="change-password.php"><i class="fa fa fa-server"></i> <span> Admin Change Password</span></a></li>
                                           
                            
                            </div>
                            <!-- /.sidebar-nav -->
                        </div>
                        <!-- /.sidebar-content -->
                    </div>