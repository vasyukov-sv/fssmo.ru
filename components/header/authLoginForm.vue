<template>
	<div class="auth-popup-container-section">
		<div class="dropdown-popup-content">
			<h4>Войти</h4>
			<div v-if="error.length > 0">{{ error }}</div>
			<form action="" method="post" @submit.prevent="send">
				<div class="form-group">
					<input type="email" name="email" v-model="email" :class="{error: $v.email.$error}" @change="$v.email.$touch()" placeholder="E-mail" autocomplete="username">
				</div>
				<div class="form-group">
					<input type="password" name="password" v-model="password" :class="{error: $v.password.$error}" @change="$v.password.$touch()" placeholder="Пароль" autocomplete="current-password">
				</div>
				<div class="form-group">
					<div class="checkbox">
						<input type="checkbox" id="auth_agree" v-model="remember">
						<label for="auth_agree">Запомнить</label>
					</div>
				</div>
				<div class="form-group-btn">
					<button type="submit" class="btn btn-gold">Войти</button>
				</div>
			</form>
			<div v-if="externalAuth.length > 0" class="socials-login">
				<h4>через соц. сети</h4>
				<div class="socials-login-list">
					<a v-for="item in externalAuth" href="#" @click.prevent="openWindow(item['link'])" :class="externalIcons[item['id']]+'-icon'" :title="item['name']"></a>
				</div>
			</div>
		</div>
		<div class="dropdown-popup-footer">
			<nuxt-link to="/personal/registration/">Зарегистрироваться</nuxt-link>
			<a href="#" @click.prevent="$emit('showRememberForm')">Забыли пароль?</a>
		</div>
	</div>
</template>

<script>
	import gql from 'graphql-tag'
	import { required, email } from 'vuelidate/lib/validators'

	const authMutation = gql`mutation ($email: String!, $password: String!, $remember: Boolean!) {
		userLogin(login: $email, password: $password, remember: $remember) {
			id
			name
			last_name
			avatar
		}
	}`;

	const externalAuthQuery = gql`query ($back_url: String!) {
		externalAuth (back_url: $back_url) {
			id
			name
			link
		}
	}`;

	export default {
		name: 'auth-login-form',
		data () {
			return {
				error: '',
				email: '',
				password: '',
				remember: false,
				externalAuth: [],
				externalIcons: {
					'vkontakte' : 'vk',
					'facebook' : 'fb',
					'odnoklassniki' : 'ok',
				},
			}
		},
		validations: {
			email: {
				required,
				email
			},
			password: {
				required
			},
		},
		methods: {
			async send ()
			{
				this.$v.$touch()

				if (this.$v.$invalid)
					return

				try
				{
					const user = await this.$apollo.mutate({
						mutation: authMutation,
						variables: {
							email: this.email,
							password: this.password,
							remember: this.remember,
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['userLogin']
					})

					this.$store.commit('currentUser', user)

					try {
						localStorage.setItem('USER_EMAIL', this.email)
					} catch {}
				}
				catch (e) {
					this.error = e.message
				}
			},
			openWindow (url)
			{
				let w = screen.width;
				let h = screen.height;

				let width = 600;
				let height = 400;

				window.open(url, '', 'status=no,scrollbars=yes,resizable=yes,width='+width+',height='+height+',top='+Math.floor((h - height)/2-14)+',left='+Math.floor((w - width)/2-5));
			}
		},
		created ()
		{
			try {
				this.email = localStorage.getItem('USER_EMAIL')
			} catch {}
		},
		mounted ()
		{
			this.$apollo.query({
				query: externalAuthQuery,
				variables: {
					back_url: window.location.href
				},
				fetchPolicy: 'cache-first'
			})
			.then((data) => {
				this.externalAuth = data.data['externalAuth']
			})
		}
	}
</script>