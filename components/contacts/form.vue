<template>
	<div class="form-container">
		<form @submit.prevent="send">
			<h4 class="white">Связаться с нами</h4>
			<div class="form-group dark-field">
				<input type="text" placeholder="Имя" v-model="name" :class="{error: $v.name.$error}" @change="$v.name.$touch()">
			</div>
			<div class="form-group dark-field">
				<input type="text" placeholder="E-mail" v-model="email" :class="{error: $v.email.$error}" @change="$v.email.$touch()">
			</div>
			<div class="form-group dark-field">
				<textarea placeholder="Сообщение" v-model="text" :class="{error: $v.text.$error}" @change="$v.text.$touch()"></textarea>
			</div>
			<div class="form-btn">
				<button type="submit" class="btn btn-gold">Отправить</button>
			</div>

		</form>
	</div>
</template>

<script>
	import gql from 'graphql-tag'
	import { required, email } from 'vuelidate/lib/validators'
	import SuccessModal from '~/components/successModal.vue'

	const feedbackForm = gql`mutation ($input: FeedbackInput!) {
		feedbackForm (data: $input)
	}`;

	export default {
		name: "contacts-form",
		data () {
			return {
				name: '',
				email: '',
				text: '',
			}
		},
		validations: {
			name: {
				required
			},
			email: {
				required,
				email
			},
			text: {
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
					await this.$apollo.mutate({
						mutation: feedbackForm,
						variables: {
							input: {
								name: this.name,
								email: this.email,
								text: this.text,
							}
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['feedbackForm']
					})

					this.$modal.show(SuccessModal, {
						text: 'Заявка отправлена'
					})

					this.name = this.email = this.text = ''
					this.$v.$reset()
				}
				catch (e)
				{
					return context.error({
						statusCode: 500,
						message: e.message,
					})
				}
			}
		}
	}
</script>