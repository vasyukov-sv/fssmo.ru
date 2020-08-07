<template>
	<div class="personal-img-wrap">
		<div class="personal-img">
			<div class="personal-img-container" :style="{backgroundImage: 'url('+image+')'}" v-if="image"></div>
			<div v-else class="personal-img-holder"></div>
			<label for="fileAva" class="avatar-file">
				<div class="personal-img-caption">Загрузить фото</div>
				<input ref="input" accept="image/jpeg,image/png" type="file" name="AVATAR" id="fileAva" @change="change" class="input-file">
			</label>
		</div>
	</div>
</template>

<script>
	import Modal from '~/components/personal/avatarPopup.vue'
	import gql from 'graphql-tag'

	const updateProfile = gql`mutation ($user: UserUpdateInput!) {
		userUpdate (user: $user) {
			id
		}
	}`;

	export default {
		name: 'avatar',
		props: {
			photo: {
				type: String,
				default: ''
			}
		},
		data () {
			return {
				image: '',
				cropper: null
			}
		},
		mounted () {
			this.image = this.photo
		},
		methods: {
			async save (image)
			{
				try
				{
					await this.$apollo.mutate({
						mutation: updateProfile,
						variables: {
							user: {
								avatar: image
							}
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)
					})

					this.image = image
					this.cancel()
				}
				catch(e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
			cancel ()
			{
				this.$modal.close()
				this.$refs['input'].value = ''
			},
			change ()
			{
				this.$modal.show(Modal, {
					image: this.$refs['input'].files[0],
				}, {
					cancel: () => {
						this.cancel()
					},
					save: (image) => {
						this.save(image)
					},
				})
			}
		}
	}
</script>