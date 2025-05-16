    </main>
    
    <footer class="bg-dark text-white py-3 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 保留所有权利。</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>基于插件的视频下载服务</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- 关于模态框 -->
    <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aboutModalLabel">关于本站</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>本站是一个集成多种视频下载功能的在线工具，基于插件系统构建。</p>
                    <p>目前支持的服务：</p>
                    <ul id="supported-services">
                        <li>推特视频下载</li>
                    </ul>
                    <p>免责声明：本站仅供学习和个人使用，请勿用于违反相关法律法规的活动。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 自定义JS -->
    <script src="assets/js/main.js"></script>
</body>
</html> 