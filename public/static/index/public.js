!function (window) {
    var docWidth = 750;

    var doc = window.document,
        docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize';

    var recalc = (function refreshRem () {
        var clientWidth = docEl.getBoundingClientRect().width;
        docEl.style.fontSize = Math.max(Math.min(20 * (clientWidth / docWidth), 11.2), 8.55) * 5 + 'px';
        return refreshRem;
    })();
    docEl.setAttribute('data-dpr', window.navigator.appVersion.match(/iphone/gi) ? window.devicePixelRatio : 1);

    if (/iP(hone|od|ad)/.test(window.navigator.userAgent)) {
        doc.documentElement.classList.add('ios');
        if (parseInt(window.navigator.appVersion.match(/OS (\d+)_(\d+)_?(\d+)?/)[1], 10) >= 8)
            doc.documentElement.classList.add('hairline');
    }

    if (!doc.addEventListener) return;
    window.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);
}(window);

// @koala-prepend "js/jquery.min.js"
// @koala-prepend "js/swiper.min.js"

var CallFunction = function(fns){
	for(index in fns){
		eval(fns[index]+"();");
	}
}

var banner = function(){
	var IndexSwiper = new Swiper('.swiper-container', {
		autoplay: {
			delay: 2000,//2秒切换一次
			disableOnInteraction: false,
		},
		grabCursor :true,
		setWrapperSize :true,
		dynamicBullets: true,
		pagination:{
			el:'.swiper-pagination',
			clickable :true,
		}
	});
	
	for(i=0;i<IndexSwiper.pagination.bullets.length;i++){
		IndexSwiper.pagination.bullets[i].onmouseover=function(){
			this.click();
		};
	}
	IndexSwiper.params.pagination.clickable = true ;
	IndexSwiper.pagination.destroy()
	IndexSwiper.pagination.init()
	IndexSwiper.pagination.bullets.eq(0).addClass('swiper-pagination-bullet-active');
}

var backtop = function(){
	$(window).scroll(function() {
		if ($(window).scrollTop() > 400) {
			$(".backtop").fadeIn(200);
		} else {
			$(".backtop").fadeOut(200);
		}
	});

	$(".backtop").click(function() {
		$('body,html').animate({scrollTop: 0},500);
		return false;
	});
}

var collect = function(){
	$(".header .collect").on('click',function(){
		var url = $(this).attr('data-url'),
			tips = $(this).attr('data-tips') || '扫码二维码或者截图收藏本站';
		var html = '<div class="modal-collect"><div class="box"><div class="title">收藏</div><div class="body">'+
		'<div class="qrcode"><img src="'+APP_PATH+'/qrcode?url='+url+'"></div><div class="tips">'+tips+'</div></div><div class="btn"><a href="javascript:;" class="close">关闭</a></div></div></div>';
		
		if($('.modal-collect').length == 0){
			$('body').append(html);
			$('.modal-collect').css('display','flex');
		}else{
			$('.modal-collect').css('display','flex');
		}
		$('.modal-collect .close').on('click',function(){
			$('.modal-collect').hide();
		});
	});
}

var indexLoad = function(){
	var el = $(".video-list.js-load"),
		page = 1,
		loaded = false;
	var loadData = function(){
		jQuery("#loading").html("<p>加载中</p>");
		if (loaded) return false;
		$.ajax({
			url:window.location.href+'?page='+page,
			type:'get',
			dataType:'json',
			success:function(result){
				if(result.data.length > 0){
					var data = result.data;
					for (var index in data){
						var html = '<li data-id="'+data[index]['id']+'"><a href="javascript:;"><div class="thumb">'+
							'<img src="'+data[index]['cover']+'"></div><div class="icon"><span class="bg"></span><span class="play"></span></div>'+
							'<div class="title">'+data[index]['title']+'</div>'+
							'<div class="desc">'+data[index]['play_num']+'观看  好评:'+data[index]['hp']+'%</div></a></li>';
						$(el).find('ul').append(html);
					}
					
					page++;
					jQuery("#loading").html("<p>加载更多</p>");
				}else{
					jQuery("#loading").html("<p>没有更多了</p>");
					loaded = true;
				}
			},
			error:function(){
				jQuery("#loading").html("<p>网络请求错误</p>");
			}
		})
	}

	loadData();

	$(el).on('click', 'li',function(){
		var id = $(this).attr('data-id');
		var loading = openLoading();
		$.ajax({
			url:APP_PATH+'/buyinfo?type=video&id='+id,
			type:'get',
			dataType:'json',
			success:function(result){
				loading.close();
				if(result.code !== 1){
					tips(result.msg);
					return false;
				}
				var video = result.data;
				if(typeof video['is_buy'] != "undefined" && video['is_buy'] == 1){
					window.location.href = APP_PATH + '/video_detail?id='+id;
					return false;
				}
				buyinfo({
					id:id,
					title:video.conf['title'],
					tips:video.conf['tips'],
					bg:video.conf['bg'],
					type:'video',
					thumb:video.info['cover'],
					name:video.info['title'],
					pays:video.pays,
					callback:function(id,type,mod){
						if(type == 'shikan'){
							window.location.href = APP_PATH+'/video_detail?type=sk&id='+id;
						}else{
							window.location.href = APP_PATH + '/pay?id='+id+'&type='+type+'&model='+mod;
						}
					}
				})
			},
			error:function(){
				tips('请求失败，请稍后重试');
			}
		})
	});
	
	jQuery('#loading').click(function(){
	    loadData();
	});
}

var catLoad = function(){
	var el = $(".video-list.js-load"),
		page = 1,
		loaded = false;

	var loadData = function(){
		jQuery("#loading").html("<p>加载中</p>");
		if(loaded) return false;
		if(window.location.href.indexOf('?') !== -1){
			var url = window.location.href + '&page='+page;
		}else{
			var url = window.location.href+'?page='+page;
		}

		$.ajax({
			url:url,
			type:'get',
			dataType:'json',
			success:function(result){
				if(result.data.length > 0){
					var data = result.data;
					for (var index in data){
						var html = '<li data-id="'+data[index]['id']+'"><a href="javascript:;"><div class="thumb">'+
							'<img src="'+data[index]['cover']+'"></div><div class="icon"><span class="bg"></span><span class="play"></span></div>'+
							'<div class="title">'+data[index]['title']+'</div>'+
							'<div class="desc">'+data[index]['play_num']+'观看  好评:'+data[index]['hp']+'%</div></a></li>';
						$(el).find('ul').append(html);
					}
					
					if(result.data.length < 20){
						jQuery("#loading").html("<p>没有更多了</p>");
					}
					page++;
					jQuery("#loading").html("<p>加载更多</p>");
				}else{
					jQuery("#loading").html("<p>没有更多了</p>");
					loaded = true;
				}
			},
			error:function(){
				jQuery("#loading").html("<p>网络请求错误</p>");
			}
		})
	}

	loadData();

	$(el).on('click', 'li',function(){
		var id = $(this).attr('data-id');
		var loading = openLoading();
		$.ajax({
			url:APP_PATH+'/buyinfo?type=video&id='+id,
			type:'get',
			dataType:'json',
			success:function(result){
				loading.close();
				if(result.code !== 1){
					tips(result.msg);
					return false;
				}
				var video = result.data;
				if(typeof video['is_buy'] != "undefined" && video['is_buy'] == 1){
					window.location.href = APP_PATH + '/video_detail?id='+id;
					return false;
				}
				buyinfo({
					id:id,
					title:video.conf['title'],
					tips:video.conf['tips'],
					bg:video.conf['bg'],
					type:'video',
					thumb:video.info['cover'],
					name:video.info['title'],
					pays:video.pays,
					callback:function(id,type,mod){
						if(type == 'shikan'){
							window.location.href = APP_PATH+'/video_detail?type=sk&id='+id;
						}else{
							window.location.href = APP_PATH + '/pay?id='+id+'&type='+type+'&model='+mod;
						}
					}
				})
			},
			error:function(){
				tips('请求失败，请稍后重试');
			}
		})
	});

	jQuery('#loading').click(function(){
	    loadData();
	});
}

var bookLoad = function(){
	var el = $(".book-list.js-load"),
		page = 1,
		loaded = false;

	var loadData = function(){
		jQuery("#loading").html("<p>加载中</p>");
		if(loaded) return false;
		if(window.location.href.indexOf('?') !== -1){
			var url = window.location.href + '&page='+page;
		}else{
			var url = window.location.href+'?page='+page;
		}

		$.ajax({
			url:url,
			type:'get',
			dataType:'json',
			success:function(result){
				if(result.data.length > 0){
					var data = result.data;
					for (var index in data){
						var html = '<li><a data-id="'+data[index]['id']+'" href="javascript:;">' +
							'<div class="name">'+data[index]['title']+'</div>' +
							'<div class="time">'+data[index]['create_time']+'</div></a></li>';
						$(el).find('ul').append(html);
					}
					
					if(result.data.length < 20){
						jQuery("#loading").html("<p>没有更多了</p>");
					}
					page++;
					jQuery("#loading").html("<p>加载更多</p>");
				}else{
					jQuery("#loading").html("<p>没有更多了</p>");
					loaded = true;
				}
			},
			error:function(){
				jQuery("#loading").html("<p>网络请求错误</p>");
			}
		})
	}

	loadData();

	$(el).on('click', 'li', function(){
		var id = $(this).find('a').attr('data-id');
		var loading = openLoading();
		$.ajax({
			url:APP_PATH+'/buyinfo?type=book&id='+id,
			type:'get',
			dataType:'json',
			success:function(result){
				loading.close();
				if(result.code !== 1){
					tips(result.msg);
					return false;
				}
				var video = result.data;
				if(typeof video['is_buy'] != "undefined" && video['is_buy'] == 1){
					window.location.href = APP_PATH + '/book_detail?id='+id;
					return false;
				}
				buyinfo({
					id:id,
					title:video.conf['title'],
					tips:video.conf['tips'],
					bg:video.conf['bg'],
					type:'book',
					name:video.info['title'],
					pays:video.pays,
					callback:function(id,type,mod){
						window.location.href = APP_PATH + '/pay?id='+id+'&type='+type+'&model='+mod;
					}
				})
			},
			error:function(){
				tips('请求失败，请稍后重试');
			}
		})
	});
	
	jQuery('#loading').click(function(){
	    loadData();
	});
}

var buyinfo = function(option){
	
	var opt = {
		id:0,
		title:'打赏后观看',
		thumb:'',
		name:'视频名称',
		pays:[],
		tips:'推荐使用浏览器打开收藏观看',
		bg:'',
		callback:'',
		type:''
	};
	
	opt = $.extend(opt,option)
	
	if(opt['bg'].length > 0){
		// var html = ' <div class="modal-buy"><div class="box bg" style="background-image:url(\''+opt['bg']+'\')">';
		var html = ' <div class="modal-buy"><div class="box bg" style="background-image:url(\''+opt['bg']+'\')">';
	}else{
		var html = ' <div class="modal-buy"><div class="box">';
	}
		
	html +='<div class="title">'+opt['title']+'</div><div class="body">';
	if(opt['thumb'].length > 0){
		html += '<div class="thumb"><img src="'+opt['thumb']+'"></div>';
	}
	html += '<div class="name">'+opt['name']+'</div>';
	if(opt['pays'].length > 0){
		html += '<div class="type">';
		for(var index in opt['pays']){
			var item = opt['pays'][index];
			item['name'] = String(item['name']).replace('{m}',item['money']);
			html += '<div class="item" data-type="'+item['type']+'"><span>'+item['name']+'</span></div>';
		}
		html += '</div>';
	}else{
		html += '<div class="type"></div>';
	}
	html += '<div class="tips">'+opt['tips']+'</div></div>'+
	'<div class="btn"><a href="javascript:;" class="close">关闭</a></div></div></div>';
		
	$("body").append(html);
	
	$(".modal-buy .type .item").on('click',function(){
		var type = $(this).attr('data-type');
		if(typeof opt['callback'] == "function"){
			opt['callback'](opt['id'],type,opt['type']);
		}
	});
	
	$(".modal-buy .close").on('click',function(){
		$(".modal-buy").fadeOut(200,function(){
			$(".modal-buy").remove();
		});
	});
	
}

var tips = function(msg,time,callback){
	var time = time || 2000;
	if($(".modal-tips").length > 0){
		$('.modal-tips').find('.tips').html(msg);
		setTimeout(function(){
			$('.modal-tips').fadeOut(200,function(){
				$(this).remove();
			});
		},time);
	}else{
		var html = '<div class="modal-tips"><div class="tips">'+msg+'</div></div>';
		$('body').append(html);
		setTimeout(function(){
			$('.modal-tips').fadeOut(200,function(){
				$(this).remove();
			});
			if(typeof callback == "function"){
				callback();
			}
		},time);
	}
}

var userChangeBind = function(name){

	var Info = {
		current:'',
		type:['yg-video', 'yg-book', 'mf-video','mf-book'],
		page:{'yg-video':1,'yg-book':1,'mf-video':1,'mf-book':1},
		current_el:'',
		start:function(){
			var urlHash = window.location.hash.replace('#','');
			this.current = this.type.includes(urlHash) ? urlHash : this.type[0];
			//激活
			this.change();
			//加载
			this.loading();
			//归零
			for (var key in this.page){
				if(key != this.current){
					this.page[key] = 1;
				}
			}
		},
		change:function(){
			$(".user-list .type .item.active").removeClass('active');
			$(".user-list .type .item[data-type='"+this.current+"']").addClass('active');
			var model = this.current.indexOf('book') > 0 ? 'book' : 'video';
			var el = $(".user-list .list").find(".item[data-type='"+this.current+"'].active");
			if(el.length > 0){
				this.current_el = $(".user-list .list").find(".item[data-type='"+this.current+"'].active");
			}else{
				var html = '<div data-type="'+this.current+'" class="item '+model+' active">' +
					'<ul></ul><div class="loading">加载中...</div></div>';
				$(".user-list .list").html(html);
				this.current_el = $(".user-list .list").find(".item[data-type='"+this.current+"'].active");
			}
		},
		getElement:function(type){
			return $(".user-list .list").find(".item[data-type='"+type+"']");
		},
		loading:function(){
			var url = APP_PATH +'/user?type='+this.current+'&page='+this.page[this.current],
				_self = this;
			$(this.current_el).find('.loading').show();
			if(this.page[this.current] === 0) return false;
			$.ajax({
				url:url,
				type:'get',
				dataType:'json',
				success:function(result){
					if(result.data.length > 0){
						_self.fetch(result.data,_self.current);
						if(result.data.length < 20){
							$(_self.current_el).find('.loading').text('没有更多了');
						}
						_self.page[_self.current]++;
					}else{
						$(_self.current_el).find('.loading').text('没有更多了');
						_self.page[_self.current] = 0;
					}
				},
				error:function(){
					$(_self.current_el).find('.loading').text('网络请求错误');
				}
			})
		},
		fetch:function(data,type){
			var model = type.indexOf('book') > 0 ? 'book' : 'video',
				html = '',
				index = 0,
				el = this.getElement(type);
			for (index in data){
				var item = data[index];
				if(model === 'video'){
					html += this.template_video(item);
				}else{
					html += this.template_book(item);
				}
			}
			$(el).find('ul').append(html);
			$(el).find('ul>li').on('click',function(){
				if(model == 'book'){
					window.location.href = APP_PATH+'/book_detail?id=' + $(this).find('a').attr('data-id');
				}else{
					window.location.href = APP_PATH+'/video_detail?id=' + $(this).attr('data-id');
				}
			});
		},
		template_video:function(data){
			return '<li data-id="'+data['id']+'">'+
				'<a href="javascript:;">' +
				'<div class="thumb"><img src="'+data['cover']+'"></div>' +
				'<div class="icon"><span class="bg"></span><span class="play"></span></div>'+
				'<div class="title">'+data['title']+'</div>'+
				'<div class="desc">'+data['play_num']+'观看  好评:'+data['hp']+'%</div>' +
				'</a></li>';
		},
		template_book:function(data){
			return '<li><a data-id="'+data['id']+'" href="javascript:;">' +
				'<div class="name">'+data['title']+'</div>' +
				'<div class="time">'+data['create_time']+'</div></a></li>';
		}
	}

	//绑定切换
	$(".user-list .type .item").on('click',function(){
		if($(this).hasClass('active')) return false;
		var path = $(this).attr('data-type');
		window.location.hash = '#'+path;
		Info.start();
	});

	Info.start();

	$(window).scroll(function() {
		if ($(window).scrollTop() + $(window).height() == $(document).height()) {
			Info.start();
		}
	});

}

var userOpenDialog = function(option){
	
	if($('.modal-login').length >  0){
		return false;
	}
	
	var default_opt = {
		title:'登录',
		field:[
			{name:'username',type:'text',notice:'请输入用户账号'},
			{name:'password',type:'password',notice:'请输入登录密码'}
		],
		btns:[
			{name:'login',type:'yes',value:'登录'},
			{name:'cancel',type:'no',value:'取消'}
		],
		callback:''
	}
	
	var opt = $.extend(default_opt,option);
	
	var getFieldValue = function(){
		var data = {};
		$('.modal-login .fn-item input').each(function(index,item){
			var fieldNmae = $(item).attr('data-name');
			data[fieldNmae] = $(item).val();
		});
		return data;
	}
	
	var methods = {
		close:function(){
			$('.modal-login').fadeOut(200,function(){
				$(this).remove();
			});
		}
	}
	
	
	var html = '<div class="modal-login"><div class="box"><div class="title">'+opt['title']+'</div>'+
		'<div class="body">';
	for(var index in opt['field']){
		var item  = opt['field'][index];
		html += '<div class="fn-item"><input class="field_'+item['name']+'" data-name="'+item['name']+'" type="'+item['type']+'" placeholder="'+item['notice']+'"></div>';
	}
	html +='<div class="fn-btn">';
	for(var index in opt['btns']){
		var item  = opt['btns'][index];
		html += '<a class="'+item['type']+'" data-type="'+item['name']+'" href="javascript:;">'+item['value']+'</a>';
	}
	html +='</div></div></div></div>';
	
	$('body').append(html);
	
	//绑定事件
	$('.modal-login .fn-btn a').on('click',function(){
		var type = $(this).attr('data-type'),
			data = getFieldValue();
		if(typeof opt['callback'] == "function"){
			opt['callback'].apply(methods,[type,data]);
		}
	});
}

var openLogin = function(){
	userOpenDialog({
		title:'用户登录',
		field:[
			{name:'username',type:'text',notice:'请输入用户账号'},
			{name:'password',type:'password',notice:'请输入登录密码'}
		],
		btns:[
			{name:'login',type:'yes',value:'立即登录'},
			{name:'cancel',type:'no',value:'取消登录'}
		],
		callback:function(type,data){
			if(type == 'login'){
				tips('登录成功');
			}else{
				this.close();
			}
		}
	});
}

var openRegister = function(){
	userOpenDialog({
		title:'用户注册',
		field:[
			{name:'username',type:'text',notice:'请输入用户账号'},
			{name:'password',type:'password',notice:'请输入登录密码'},
			{name:'repassword',type:'password',notice:'请输入重复输入密码'}
		],
		btns:[
			{name:'register',type:'yes',value:'立即注册'},
			{name:'cancel',type:'no',value:'关闭窗口'}
		],
		callback:function(type,data){
			var url = window.location.href;
			url = url.replace('?ts_login=1','');
			url = url.replace('&ts_login=1','');

			if(type == 'register'){
				if(data.username == ""){
					tips('用户账号不能为空');
					return;
				}
				if(data.password == ""){
					tips('登录密码不能为空');
					return;
				}

				if(data.repassword == ""){
					tips('重复密码不能为空');
					return;
				}

				if(data.password != data.repassword){
					tips('重复密码不正确');
					return;
				}
				$.ajax({
					url:APP_PATH+'/register',
					type:'post',
					data:data,
					dataType:'json',
					success:function(result){
						if(result.code == 1){
							tips(result.msg,2000,function(){
								window.location.href = url;
							});
						}else{
							tips(result.msg);
						}
					}
				})
			}else{
				this.close();
			}
		}
	});
}

var openLoading = function(){
	var html = '<div class="modal-loading"><div class="box"><i></i><i class="delay"></i><i></i></div></div>';
	$('body').append(html);
	return {
		close:function(){
			$('.modal-loading').remove();
		}
	};
}

var videoSearch = function(){
	$('.search').keydown(function (e) {
		var $s = $('.search').val();
		if (e.keyCode === 13){
			if($s.length <= 0){
				tips('关键词不能为空');
				return;
			}
			if($s.length < 2){
				tips('关键词不能小于2个');
				return;
			}
			window.location.href = APP_PATH+'/video?keyword='+$s;
		}
	});
}

var bookSearch = function(){
	$('.search').keydown(function (e) {
		var $s = $('.search').val();
		if (e.keyCode === 13){
			if($s.length <= 0){
				tips('关键词不能为空');
				return;
			}
			if($s.length < 2){
				tips('关键词不能小于2个');
				return;
			}
			window.location.href = APP_PATH+'/book?keyword='+$s;
		}
	});
}

var userLogout = function(){
	$(".logout a").on('click',function(){
		$.ajax({
			url:APP_PATH+'/logout',
			type:'get',
			dataType:'json',
			success:function(){
				tips('退出登录成功',2000,function(){
					window.location.reload();
				});
			}
		})
	});
}

var likeDing = function(){
	$("#like-ding").on('click',function(){
		$.ajax({
			url:APP_PATH+'/like?id='+$(this).attr('data-id'),
			type:'get',
			dataType:'json',
			success:function(result){
				if(result.code === 1){
					tips(result.msg,2000,function(){
						window.location.reload();
					});
				}else{
					tips(result.msg);
				}
			}
		})
	});
}