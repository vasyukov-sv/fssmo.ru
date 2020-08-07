<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container">
			<form action="" method="post" @submit.prevent="send">
				Email: {{ $route.query['USER_LOGIN'] }}<br><br>

				<div class="form-group form-group-label-inline">
					<label class="form-label">новый пароль</label>
					<div class="input-container">
						<input type="password" name="password" v-model="password" autocomplete="new-password" :class="{error: $v.password.$error}" @change="$v.password.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">повторите новый пароль</label>
					<div class="input-container">
						<input type="password" name="password_confirm" v-model="passwordConfirm" autocomplete="new-password" :class="{error: $v.passwordConfirm.$error}" @change="$v.passwordConfirm.$touch()">
					</div>
				</div>

				<button type="submit">Сменить пароль</button>

			</form>
		</section>
	</div>
</template>

<script>
	import SuccessModal from '~/components/successModal.vue'
	import { required, sameAs } from 'vuelidate/lib/validators'
	import gql from 'graphql-tag'

	const updatePasswordQuery = gql`mutation ($input: UserUpdatePasswordInput!) {
		userUpdatePassword (data: $input)
	}`;

	export default {
		name: 'password',
		middleware: 'guest',
		async asyncData (context) {
			await context.store.dispatch('getPageInfo')
		},
		data () {
			return {
				password: '',
				passwordConfirm: '',
			}
		},
		validations: {
			password: {
				required
			},
			passwordConfirm: {
				required,
				sameAsPassword: sameAs('password')
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
					await this.$apollo.mutate({
						mutation: updatePasswordQuery,
						variables: {
							input: {
								login: this.$route.query['USER_LOGIN'],
								checkword: this.$route.query['USER_CHECKWORD'],
								password: this.password,
								confirm: this.passwordConfirm,
							}
						}
					})
					.then((data) =>
					{
						if (data.errors !== undefined)
						{
							this.$modal.show(SuccessModal, {
								text: data.errors[0].message,
								error: true,
							})
						}
						else
							this.$router.push('/')
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			}
		}
	}
</script>