<template>
	<div class="avatar-crop-popup">
		<div class="avatar-crop-container">
			<div class="row">
				<div class="col-sm-9">
					<div class="crop-image-large">
						<img src ref="image" class="image" alt>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="crop-preview">
						<img alt src ref="preview">
					</div>
				</div>
			</div>
		</div>
		<div class="avatar-crop-btns form">
			<div class="row">
				<div class="col-sm-9">
					<div class="row">
						<div class="col-6">
							<button @click.prevent="$emit('cancel')" class="btn btn-blue-border">
								Отмена
							</button>
						</div>
						<div class="col-6">
							<button @click.prevent="save" class="btn btn-gold">
								Сохранить
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<!--suppress NpmUsedModulesInstalled -->
<script>
	import Cropper from 'cropperjs'
	import 'cropperjs/dist/cropper.css'

	export default {
		name: "avatarPopup",
		props: {
			image: {
				validator: function (value) {
					return value instanceof File
				},
			}
		},
		data () {
			return {
				cropper: null,
				loader: false,
			}
		},
		mounted ()
		{
			let preview = this.$refs['preview']
			let reader = new FileReader()

			reader.onload = (e) =>
			{
				this.$refs['image'].src = e.target['result']

				this.cropper = new Cropper(this.$refs['image'], {
					aspectRatio: 1,
					modal: false,
					highlight: false,
					background: false,
					autoCropArea: 1,
					movable: false,
					rotatable: false,
					scalable: false,
					zoomable: false,
					zoomOnTouch: false,
					zoomOnWheel: false,
					ready: () => {
						preview.src = this.cropper.getCroppedCanvas({ fillColor: '#fff' }).toDataURL('image/jpeg');
					},
					cropend: (e) =>
					{
						let imageData = this.cropper.getData();
						let previewAspectRatio = imageData.width / imageData.height;

						let previewWidth = preview.width;
						let previewHeight = previewWidth / previewAspectRatio;
						let imageScaledRatio = e.target.width / previewWidth;

						preview.parentElement.height = previewHeight;

						preview.style.width = imageData.naturalWidth / imageScaledRatio;
						preview.style.height = imageData.naturalHeight / imageScaledRatio;
						preview.style.marginLeft = -e.target.x / imageScaledRatio;
						preview.style.marginTop = -e.target.y / imageScaledRatio;

						preview.src = this.cropper.getCroppedCanvas({ fillColor: '#fff' }).toDataURL('image/jpeg');
					}
				})
			}

			try {
				reader.readAsDataURL(this.image)
			}
			catch (e)
			{
				this.$modal.alert({
					title: 'Ошибка',
					content: e.message
				})
			}
		},
		beforeDestroy()
		{
			if (!this.cropper)
				return

			this.cropper.destroy()
			this.cropper = null
		},
		methods: {
			save ()
			{
				if (!this.cropper)
					return

				this.loader = true

				try
				{
					const data = this.cropper.getImageData()

					const image = this.cropper.getCroppedCanvas({
						maxWidth: (data['aspectRatio'] <= 1 ? 500 : Infinity),
						maxHeight: (data['aspectRatio'] >= 1 ? 500 : Infinity),
						fillColor: '#fff'
					})

					this.$emit('save', image.toDataURL('image/jpeg'))
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