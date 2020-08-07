<template>
	<div class="page-section-content">
		<div class="event-gallery-container">
			<div class="event-gallery-header" v-if="photos.length > 0">{{photos.length}} {{ photos.length | morph('Фотография', 'Фотографии', 'Фотографий') }}</div>
			<div class="event-gallery-header" v-else>Нет фотографий</div>
			<div class="gallery-list">
				<div class="gallery-col" v-for="(item, i) in photos" :class="{'vertical': item.ratio < 1}">
					<div class="gallery-item" @click="popupIndex = i">
						<v-lazy-image :src="item.preview" />
					</div>
				</div>
			</div>
			<client-only>
				<vue-gallery :images="photosSrc" :index="popupIndex" @close="popupIndex = null"></vue-gallery>
			</client-only>
		</div>
	</div>
</template>

<script>
	import VLazyImage from 'v-lazy-image';
	import gql from 'graphql-tag';

	const photoListQuery = gql`query ($id: String!) {
		photos (competition: $id) {
			title
			preview
			src
			ratio
		}
	}`;

	export default {
		props: {
			competition: {
				type: Object
			}
		},
		components: {
			VLazyImage,
		},
		data () {
			return {
				popupIndex: null
			}
		},
		async asyncData (context)
		{
			try
			{
				const photos = await context.app.$apollo.query({
					query: photoListQuery,
					variables: {
						id: context.params.id,
					},
					fetchPolicy: 'cache-first'
				})
				.then((result) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['photos']
				})

				return {
					photos: photos
				}
			}
			catch (e)
			{
				return context.error({
					statusCode: 500,
					message: e.message,
				})
			}
		},
		head () {
			return {
				titleTemplate: '%s - Фотогалерея'
			}
		},
		computed: {
			photosSrc ()
			{
				let result = [];

				this.photos.forEach((item) => {
					result.push(item['src'])
				});

				return result;
			}
		}
	}
</script>