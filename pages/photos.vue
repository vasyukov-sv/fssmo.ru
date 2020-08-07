<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>

		<section class="container">
			<nav class="page-list-nav">
				<ul>
					<li>
						<a href="#" @click.prevent="setActive(0)" :class="{ active: isActive(0) }">Все</a>
					</li>
					<li v-for="discipline in disciplines">
						<a href="#" @click.prevent="setActive(discipline['id'])" :class="{ active: isActive(discipline['id']) }">{{ discipline['title'] }}</a>
					</li>
				</ul>
			</nav>

			<div class="photoes-list-container">
				<div class="row">
					<PhotoItem v-for="album in photoAlbums" :key="album['id']" :item="album"/>
				</div>
			</div>
		</section>

		<div v-if="allowLoadMore" class="loadmore-btn">
			<a href="#" @click.prevent="loadMore" class="btn btn-blue-border">показать еще</a>
		</div>

		<section class="blue-img-section">
			<div class="container">
				<BottomResults/>
				<BottomRating/>
			</div>
		</section>

		<FutureEventsBottom/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import PhotoItem from '~/components/photoItem.vue'
	import { disciplinesQuery, photoAlbumsQuery } from '~/utils/query.gql'

	const limit = 9;

	export default {
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
			PhotoItem,
		},
		async asyncData (context)
		{
			try
			{
				let disciplinesRequest = context.app.$apollo.query({
					query: disciplinesQuery,
					fetchPolicy: 'cache-first',
				})

				let photoAlbumsRequest = context.app.$apollo.query({
					query: photoAlbumsQuery,
					variables: {
						page: 1,
						limit: limit,
						filter: {},
					}
				})

				let [disciplines, photoAlbums] = await Promise.all([
					disciplinesRequest,
					photoAlbumsRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([disciplines, albums]) =>
				{
					if (typeof disciplines.errors !== 'undefined')
						throw new Error(disciplines.errors[0].message)

					if (typeof albums.errors !== 'undefined')
						throw new Error(albums.errors[0].message)

					return [disciplines.data['disciplines'], albums.data['items']]
				})

				return {
					allowLoadMore: photoAlbums.length >= limit,
					photoAlbums: photoAlbums || [],
					disciplines: disciplines || [],
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
		data()
		{
			return {
				page: 1,
				allowLoadMore: true,
				activeDiscipline: 0
			};
		},
		methods: {
			isActive (id) {
				return this.activeDiscipline === id;
			},
			setActive (id) {
				this.activeDiscipline = id;
				this.updateAlbumsList(id);
			},
			async updateAlbumsList ()
			{
				try
				{
					const photos = await this.$apollo.query({
						query: photoAlbumsQuery,
						variables: {
							page: 1,
							limit: limit,
							filter: {
								discipline: this.activeDiscipline
							}
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['items']
					})

					this.photoAlbums = photos
					this.allowLoadMore = !(photos.length < limit)
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
			async loadMore ()
			{
				this.page++

				try
				{
					const photos = await this.$apollo.query({
						query: photoAlbumsQuery,
						variables: {
							page: this.page,
							limit: limit,
							filter: {
								discipline: this.activeDiscipline
							}
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['items']
					})

					this.allowLoadMore = !(photos.length < limit)

					photos.forEach((item) => {
						this.photoAlbums.push(item)
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
		},
	}
</script>