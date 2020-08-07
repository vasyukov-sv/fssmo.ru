const webpack = require('webpack')

const proxyUrl = process.env.PROXY_URL || 'http://fssmo.x-demo.ru'

module.exports = {
	head: {
		title: 'Федерация стендовой стрельбы Московской области',
		meta: [
			{ charset: 'utf-8' },
			{ name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no' },
			{ name: 'msapplication-TileColor', content: '#ffffff' },
			{ name: 'theme-color', content: '#ffffff' },
		],
		link: [
			{ rel: 'icon', type: 'image/x-icon', href: '/favicon/favicon.ico' },
			{ rel: 'apple-touch-icon', sizes: '180x180', href: '/favicon/apple-touch-icon.png' },
			{ rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon/favicon-32x32.png' },
			{ rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon/favicon-16x16.png' },
			{ rel: 'manifest', href: '/favicon/site.webmanifest' },
			{ rel: 'mask-icon', href: '/favicon/safari-pinned-tab.svg',  color: '#5bbad5' },
		]
  	},
	router: {
		base: '/',
		prefetchLinks: false,
	},
	css: [
		'~assets/styles/main.scss',
	],
	pageTransition: {
		name: 'page-switch',
		mode: 'out-in'
	},
	plugins: [
		{src: '~/plugins/swiper.js', ssr: false},
		'~/plugins/validate.js',
		'~/plugins/apollo.js',
		{src: '~/plugins/stickykit.js', ssr: false},
		{src: '~/plugins/svg-progress-bar.js', ssr: false},
		{src: '~/plugins/calendar.js', ssr: false},
		{src: '~/plugins/modal.js', ssr: false},
    	'~/plugins/global.js',
    	{src: '~/plugins/modernizr.js', ssr: false},
    	{src: '~/plugins/maps.js', ssr: false},
    	{src: '~/plugins/gallery.js', ssr: false},
		{src: '~/plugins/events.js', ssr: false},
		{src: '~/plugins/datepicker.js', ssr: false},
	],
	modern: process.env.NODE_ENV === 'production',
	render: {
		compressor: false,
	},
	loading: false,
	build: {
		indicator: false,
		cssSourceMap: false,
		extractCSS: process.env.NODE_ENV === 'production',
		publicPath: '/static/',
		loaders: {
			vue: {
				compilerOptions: {
					preserveWhitespace: true
				}
			}
		},
		babel: {
			plugins: ['babel-plugin-graphql-tag']
		},
		optimizeCSS: {
			cssProcessorPluginOptions: {
				preset: ['default', {
					discardComments: {
					removeAll: true
				}}],
			},
		},
		extend (config, {isDev, isClient})
		{
			if (isDev && isClient)
			{
				config.module.rules.push({
					enforce: 'pre',
					test: /\.(js|vue)$/,
					exclude: /(node_modules)/
				});
			}

			config.module.rules.push({
				test: /\.(graphql|gql)$/,
				exclude: /node_modules/,
				loader: 'graphql-tag/loader'
			})

			config.plugins.push(new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/))
		}
	},
	messages: {
		error_404: 'Страница не найдена',
	},
	modules: [
		'@nuxtjs/proxy',
		'@nuxtjs/sentry',
		['@nuxtjs/google-tag-manager', {
			id: 'GTM-WZZGCJ6'
		}]
	],
	proxy: {
		'/upload': proxyUrl,
		'/bitrix': proxyUrl,
		'/api': {target: proxyUrl, cookieDomainRewrite: {"*": ""}},
	},
	vue: {
		config: {
			productionTip: false
		}
	},
	sentry: {
		dsn: 'https://b9d4af432a184ece860f35f39359325d@sentry.io/1510755',
		options: {
			disabled: process.env.NODE_ENV !== 'production'
		},
		clientIntegrations: {
			ReportingObserver: false,
		},
		clientConfig: {
			beforeSend (event)
			{
				if (event.message && event.message.indexOf('gCrWeb') > -1)
					return null

				return event
			},
		},
	},
};

