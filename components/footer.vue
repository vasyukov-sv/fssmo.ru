<template>
	<footer class="footer">

		<div class="calendar-info-section">
			<div class="container">
				<div class="calendar-info-section-content">
					<div class="bottom-img-title">Народный календарь</div>
					<div class="bottom-img-text">
						<p>Полный календарь соревнований по стендовой стрельбе. <br /> <br />
						Каждый желающий может внести информацию по любому турниру. <br /> <br />
						Удобный фильтр для поиска соревнований.</p>
					</div>
					<div class="bottom-img-btn">
						<nuxt-link to="/calendar" class="btn btn-gold">Перейти в календарь</nuxt-link>
					</div>
				</div>
			</div>
		</div>

		<div v-if="$nuxt.nuxt.err === null" class="footer-top">
			<Sponsors/>
		</div>
		<div class="footer-bottom">
			<div class="container-fluid">
				<div class="footer-bottom-content">
					<div class="row">
						<div class="col-lg-3">
							<div class="logo-container">
								<nuxt-link to="/" class="logo-link"><img src="/images/logo.svg" alt="logo"/></nuxt-link>
								<div class="logo-caption">
									<nuxt-link to="/" class="logo-title">ФССМО</nuxt-link>
									<div class="logo-text">Федерация стендовой стрельбы Московской области</div>
								</div>
							</div>
						</div>
						<div class="col-lg-6 col-sm-9">
							<div class="row">
								<div class="col-sm-12 col-7">
									<ul class="footer-menu footer-menu-top">
										<li v-for="item in $store.getters.getMainMenu">
											<nuxt-link :to="item.url">{{ item.title }}</nuxt-link>
										</li>
									</ul>
								</div>
								<div class="col-sm-12 col-5">
									<ul class="footer-menu footer-menu-bottom">
										<li v-for="item in $store.getters.getTopMenu">
											<nuxt-link :to="item.url">{{ item.title }}</nuxt-link>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="col-sm-3">
							<div class="footer-contacts" v-editor:block="[{code: 'CONTACTS_PHONE', name: 'Телефон', type: 'TEXT'}, {code: 'CONTACTS_EMAIL', name: 'Email', type: 'TEXT'}]">
								<a :href="'tel:'+$store.state.area['CONTACTS_PHONE'].replace(/([- ()])/ig, '')" class="header-phone">
									<span class="icon-phone"></span>{{ $store.state.area['CONTACTS_PHONE'] }}
								</a><br>
								<a :href="'mailto:'+$store.state.area['CONTACTS_EMAIL']" class="header-mail">
									<span class="icon-mail"></span>{{ $store.state.area['CONTACTS_EMAIL'] }}
								</a>
							</div>
						</div>
					</div>
				</div>
				<div class="footer-bottom-line">
					<div class="row">
						<div class="col-6">
							<div class="copyright">Все права защищены ©&nbsp;2018</div>
						</div>
						<div class="col-6">
							<div class="made-by-container">
								<div class="made-by">
									Сайт сделан
									<a href="https://olympia.digital/" target="_blank" title="OLYMPIA">с любовью,<br>эмпатией и смыслом</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
</template>

<script>
	import Sponsors from '~/components/footerSponsors.vue'

	export default {
		name: "pageFooter",
		components: {
			Sponsors,
		},
	}
</script>