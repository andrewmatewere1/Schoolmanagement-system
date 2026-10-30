  <nav class="navbar top-navbar bg-white box-shadow">
            	<div class="container-fluid">
                    <div class="row">
                        <div class="navbar-header no-padding">
                			<a class="navbar-brand" href="dashboard.php">
                			    <?php echo e(srms_school_name()); ?> | <?php echo e($_SESSION['alogin']??'Admin'); ?>
                			</a>
                            <span class="small-nav-handle hidden-sm hidden-xs"><i class="fa fa-outdent"></i></span>
                			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
                				<span class="sr-only">Toggle navigation</span>
                				<i class="fa fa-ellipsis-v"></i>
                			</button>
                            <button type="button" class="navbar-toggle mobile-nav-toggle" aria-controls="srms-left-sidebar" aria-expanded="false" aria-label="Open navigation menu">
                				<i class="fa fa-bars"></i>
                			</button>
                		</div>
                        <!-- /.navbar-header -->

                		<div class="collapse navbar-collapse" id="navbar-collapse-1">
                			<ul class="nav navbar-nav" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                                <li class="hidden-sm hidden-xs"><a href="#" class="user-info-handle"><i class="fa fa-user"></i></a></li>
                                <li class="hidden-sm hidden-xs"><a href="#" class="full-screen-handle"><i class="fa fa-arrows-alt"></i></a></li>
                       
                				<li class="hidden-xs hidden-xs"><!-- <a href="#">My Tasks</a> --></li>
                               
                			</ul>
                            <!-- /.nav navbar-nav -->

                			<ul class="nav navbar-nav navbar-right" data-dropdown-in="fadeIn" data-dropdown-out="fadeOut">
                                <?php
                                $srmsUnread=0;
                                try {
                                    if (is_file(__DIR__.'/phase3.php')) { require_once __DIR__.'/phase3.php';
                                        if (!empty($_SESSION['admin_id'])) $srmsUnread=notification_unread_count($dbh,'admin',(int)$_SESSION['admin_id']);
                                    }
                                } catch (Throwable $ignore) {}
                                ?>
                                <li><a href="notifications.php"><i class="fa fa-bell"></i> Notifications <?php if($srmsUnread): ?><span class="badge" style="background:#d9534f;"><?php echo $srmsUnread; ?></span><?php endif; ?></a></li>
                				    <li><a href="admin-profile.php"><i class="fa fa-user"></i> My Profile</a></li><li><a href="logout.php" class="color-danger text-center"><i class="fa fa-sign-out"></i> Logout</a></li>
                					
                		
                            
                			</ul>
                            <!-- /.nav navbar-nav navbar-right -->
                		</div>
                		<!-- /.navbar-collapse -->
                    </div>
                    <!-- /.row -->
            	</div>
            	<!-- /.container-fluid -->
            </nav>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
              var button = document.querySelector('.mobile-nav-toggle');
              var sidebar = document.querySelector('.left-sidebar');
              if (!button || !sidebar) return;
              button.addEventListener('click', function(event) {
                event.preventDefault();
                var open = !sidebar.classList.contains('mobile-open');
                sidebar.classList.toggle('mobile-open', open);
                document.body.classList.toggle('srms-menu-open', open);
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
                button.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
              });
              sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                  if (window.innerWidth <= 991 && !link.closest('.has-children')) {
                    sidebar.classList.remove('mobile-open');
                    document.body.classList.remove('srms-menu-open');
                    button.setAttribute('aria-expanded', 'false');
                  }
                });
              });
            });
            </script>
