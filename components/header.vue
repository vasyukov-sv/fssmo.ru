<template>
	<header class="page-header" :class="{'menu-opened': menuOpened}">
		<div class="container-fluid">
			<div class="page-header-content">
				<div class="logo-container">
					<nuxt-link to="/" class="logo-link"><img src="/images/logo.svg" alt="logo"/></nuxt-link>
					<div class="logo-caption">
						<nuxt-link to="/" class="logo-title">ФССМО</nuxt-link>
						<div class="logo-text">Федерация стендовой стрельбы Московской области</div>
					</div>
				</div>
				<div class="header-top">
					<div class="header-top-left">
						<ul class="header-top-menu">
							<li v-for="item in $store.getters.getTopMenu">
								<nuxt-link :to="item.url">{{ item.title }}</nuxt-link>
							</li>
						</ul>
					</div>
					<div class="header-top-right">
						<div class="header-top-ctrls">
							<div v-if="!$store.getters.isAuthorized" class="auth-popup">
								<auth-form></auth-form>
							</div>
							<div v-else class="auth-popup personal-popup">
								<button class="login-link" :class="{'active': personalPopup}" @click="personalPopup =! personalPopup" :title="userName">
									<div class="person-top-link">
										<span class="personal-top-img" :style="{backgroundImage: 'url('+avatar+')'}" :class="{'no-img': !avatar}"></span>
										{{ userName }}
									</div>
								</button>
								<transition name="fade">
									<div class="auth-popup-container" v-if="personalPopup" v-clickaway="closePersonalPopup">
										<div class="dropdown-popup-content">
											<ul>
												<li><nuxt-link to="/personal/">Мой профиль</nuxt-link></li>
												<li><nuxt-link to="/personal/competitions/">Мои соревнования</nuxt-link></li>
												<li><nuxt-link to="/personal/stats/">Моя статистика</nuxt-link></li>
											</ul>
										</div>
										<div class="dropdown-popup-footer">
											<a href="#" @click.prevent="logout">Выйти</a>
										</div>
									</div>
								</transition>
							</div>
							<a v-editor:block="[{code: 'CONTACTS_PHONE', name: 'Телефон', type: 'TEXT'}]" :href="'tel:'+$store.state.area['CONTACTS_PHONE'].replace(/([- ()])/ig, '')" class="header-phone">
								<span class="icon-phone"></span>{{ $store.state.area['CONTACTS_PHONE'] }}
							</a>
						</div>
						<nuxt-link to="/enter/" class="btn btn-gold hidden-xs">Присоединиться</nuxt-link>
						<button class="menu-toggle" @click="menuOpened =! menuOpened"><span></span></button>
					</div>
				</div>
				<div class="header-bottom">
					<div class="header-bottom-menu">
						<div class="header-bottom-menu-container">
							<ul class="header-bottom-menu-list">
								<li v-for="item in $store.getters.getMainMenu">
									<nuxt-link :to="item.url">
										<span class="menu-icon"><img :src="item.icon" alt=""></span>{{ item.title }}
									</nuxt-link>
								</li>
							</ul>
							<div class="mobile-menu-right">
								<ul class="header-top-menu-mobile">
									<li v-for="item in $store.getters.getTopMenu">
										<nuxt-link :to="item.url">{{ item.title }}</nuxt-link>
									</li>
								</ul>
								<div class="mobile-header-menu-info">
									<a :href="'tel:'+$store.state.area['CONTACTS_PHONE'].replace(/([- ()])/ig, '')" class="header-phone">
										<span class="icon-phone"></span>{{ $store.state.area['CONTACTS_PHONE'] }}
									</a>
									<nuxt-link to="/enter/" class="btn btn-gold visible-xs">Присоединиться</nuxt-link>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
</template>

<script>
	import AuthForm from './header/auth.vue'
	import gql from 'graphql-tag'
	import { directive as clickaway } from 'vue-clickaway';

	const logoutMutation = gql`mutation {
		userLogout
	}`;

	export default {
		directives: {
			clickaway
		},
		components: {
			AuthForm
		},
		data() {
			return {
				personalPopup: false,
				menuOpened: false
            }
		},
		watch: {
			'$route' () {
				this.closePersonalPopup();
				this.menuOpened = false;
			}
		},
		computed: {
			userName () {
				return this.$store.state.user && this.$store.state.user.name ? this.$store.state.user.name+' '+this.$store.state.user.last_name : 'Профиль';
			},
			avatar () {
				return this.$store.state.user ? this.$store.state.user.avatar : null;
			}
		},
		methods: {
			async logout ()
			{
				try
				{
					await this.$apollo.mutate({
						mutation: logoutMutation,
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return true
					})

					this.$store.commit('currentUser', null)
					this.$bus.$emit('logout')
				}
				catch (e)
				{
					return context.error({
						statusCode: 500,
						message: e.message,
					})
				}
			},
			closePersonalPopup () {
				this.personalPopup = false;
			}
		}
	}
</script>