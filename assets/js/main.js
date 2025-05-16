/**
 * 主JavaScript文件
 * 处理前端交互逻辑
 */

$(document).ready(function() {
    // 当前选中的插件ID
    let currentPluginId = 'multi_downloader'; // 默认选中多源下载插件
    
    // 插件按钮点击事件
    $('.plugin-btn').on('click', function() {
        const pluginId = $(this).data('plugin');
        selectPlugin(pluginId);
    });
    
    // 添加支持网站弹窗按钮
    $('body').append(`
        <button id="supported-sites-btn" class="btn btn-info btn-sm position-fixed" 
                style="bottom: 20px; right: 20px; z-index: 1000;">
            支持的网站列表
        </button>
        
        <div class="modal fade" id="supportedSitesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">支持的视频网站列表</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>本工具支持1000+视频网站的下载，包括但不限于：</p>
                        <div class="row">
                            <div class="col-md-4">
                                <ul>
                                    <li>YouTube</li>
                                    <li>哔哩哔哩 (Bilibili)</li>
                                    <li>推特 (Twitter/X)</li>
                                    <li>抖音/TikTok</li>
                                    <li>Facebook</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul>
                                    <li>Instagram</li>
                                    <li>Vimeo</li>
                                    <li>Twitch</li>
                                    <li>Dailymotion</li>
                                    <li>Reddit</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul>
                                    <li>Pornhub</li>
                                    <li>Xvideos</li>
                                    <li>爱奇艺</li>
                                    <li>腾讯视频</li>
                                    <li>...等1000+网站</li>
                                </ul>
                            </div>
                        </div>
                        <p class="mt-3">完整支持列表请参考: <a href="https://ytdl-org.github.io/youtube-dl/supportedsites.html" target="_blank">YouTube-DL支持的网站</a></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    // 支持网站按钮点击事件
    $('#supported-sites-btn').on('click', function() {
        var supportedSitesModal = new bootstrap.Modal(document.getElementById('supportedSitesModal'));
        supportedSitesModal.show();
    });
    
    // 设置默认选中的插件
    selectPlugin(currentPluginId);
    
    // 表单提交处理
    $('#download-form').on('submit', function(e) {
        e.preventDefault();
        processUrl();
    });
    
    /**
     * 选择插件
     * @param {string} pluginId 插件ID
     */
    function selectPlugin(pluginId) {
        currentPluginId = pluginId;
        
        // 更新UI，高亮选中的插件按钮
        $('.plugin-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(`.plugin-btn[data-plugin="${pluginId}"]`).removeClass('btn-outline-primary').addClass('btn-primary');
        
        // 更新表单的placeholder和其他相关文本
        updatePlaceholder(pluginId);
    }
    
    /**
     * 根据选中的插件更新表单的placeholder
     * @param {string} pluginId 插件ID
     */
    function updatePlaceholder(pluginId) {
        let placeholder = '';
        
        switch (pluginId) {
            case 'twitter_downloader':
                placeholder = '粘贴推特视频链接 (例如: https://twitter.com/user/status/123456789)';
                break;
            case 'multi_downloader':
                placeholder = '粘贴任意支持的视频网站链接 (例如: YouTube, 哔哩哔哩, TikTok 等)';
                break;
            default:
                placeholder = '粘贴您要下载的视频链接...';
        }
        
        $('#url').attr('placeholder', placeholder);
    }
    
    /**
     * 处理URL
     */
    function processUrl() {
        const url = $('#url').val().trim();
        
        if (!url) {
            showError('请输入链接');
            return;
        }
        
        // 显示加载中
        showLoading();
        
        // 发送AJAX请求到后端
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: {
                action: 'process_url',
                plugin_id: currentPluginId,
                url: url
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showResult(response.data);
                } else {
                    showError(response.message || '处理链接失败', response.logs || []);
                }
            },
            error: function() {
                hideLoading();
                showError('服务器错误，请稍后再试');
            }
        });
    }
    
    /**
     * 显示加载中动画
     */
    function showLoading() {
        // 隐藏结果区域
        $('#result-area').hide();
        
        // 在结果内容区域显示加载动画
        $('#result-content').html('<div class="text-center"><div class="loader"></div><p>正在获取下载链接，请稍候...</p></div>');
        
        // 显示结果区域
        $('#result-area').fadeIn();
    }
    
    /**
     * 隐藏加载动画
     */
    function hideLoading() {
        // 不需要特殊处理，结果会直接替换加载动画
    }
    
    /**
     * 显示错误信息
     * @param {string} message 错误信息
     */
    function showError(message, logs) {
        $('#result-content').html(`
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> ${message}
            </div>
        `);
        $('#result-area').fadeIn();
        // 显示日志
        if (logs && logs.length > 0) {
            $('#log-content').html('<b>详细日志：</b><br>' + logs.map(l => $('<div>').text(l).html()).join('<br>')).show();
        } else {
            $('#log-content').hide();
        }
    }
    
    /**
     * 显示结果
     * @param {object} data 包含下载链接等信息的对象
     */
    function showResult(data) {
        let html = '';
        
        if (data.formats && data.formats.length > 0) {
            html += '<div class="video-info mb-3">';
            
            if (data.thumbnail) {
                html += `<div class="text-center mb-3"><img src="${data.thumbnail}" alt="视频缩略图" class="img-fluid rounded" style="max-height: 200px;"></div>`;
            }
            
            // 增强视频信息显示
            html += `<h5 class="text-center mb-3">${data.title}</h5>`;
            
            // 添加视频详细信息卡片
            html += '<div class="card mb-3"><div class="card-body">';
            html += '<div class="row">';
            
            // 左侧：视频信息
            html += '<div class="col-md-8">';
            if (data.uploader) {
                html += `<p class="mb-1"><strong>上传者：</strong>${data.uploader}</p>`;
            }
            if (data.duration) {
                html += `<p class="mb-1"><strong>时长：</strong>${data.duration}</p>`;
            }
            if (data.upload_date) {
                html += `<p class="mb-1"><strong>上传日期：</strong>${data.upload_date}</p>`;
            }
            if (data.extractor) {
                html += `<p class="mb-1"><strong>来源：</strong>${data.extractor}</p>`;
            }
            html += '</div>';
            
            // 右侧：下载统计
            html += '<div class="col-md-4">';
            html += `<div class="text-center"><span class="badge bg-primary rounded-pill">${data.formats.length}种格式可用</span></div>`;
            if (data.formats[0] && data.formats[0].resolution) {
                html += `<div class="text-center mt-2"><span class="badge bg-success rounded-pill">最高分辨率: ${data.formats[0].resolution}</span></div>`;
            }
            html += '</div>';
            
            html += '</div></div></div>';
            
            html += '</div>';
            
            html += '<div class="download-options">';
            html += '<h6>选择下载质量:</h6>';
            
            // 使用表格显示
            html += '<div class="table-responsive mb-3"><table class="table table-sm table-bordered">';
            html += '<thead class="table-light"><tr>';
            html += '<th>质量</th>';
            html += '<th>操作</th>';
            html += '</tr></thead><tbody>';
            
            data.formats.forEach(function(format, index) {
                // 解析质量信息
                const qualityInfo = format.quality || '未知质量';
                
                html += '<tr>';
                html += `<td><strong>${qualityInfo}</strong></td>`;
                html += `<td><a href="${format.url}" class="btn btn-sm btn-success" target="_blank" download>下载</a></td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            if (data.note) {
                html += `<div class="alert alert-info mt-3">${data.note}</div>`;
            }
        } else {
            html = '<div class="alert alert-warning">未找到可下载的视频</div>';
        }
        
        $('#result-content').html(html);
        $('#result-area').fadeIn();
        // 显示日志
        if (data.logs && data.logs.length > 0) {
            $('#log-content').html('<b>详细日志：</b><br>' + data.logs.map(l => $('<div>').text(l).html()).join('<br>')).show();
        } else {
            $('#log-content').hide();
        }
    }
}); 