<template>
		<div v-if="pages > 1" class="pagination-wrap">
			<div class="pagination-container">
				<a href="#" @click.prevent="$emit('change', data.page - 1)" :class="{disabled: data.page <= 1}" class="pagin-arr arr-prev">Сюда</a>
				<div  class="pagination-pages">
					<div v-for="item in items" :class="{active: data.page === item}" class="pagin-page">
						<a v-if="item > 0" href="#" @click.prevent="$emit('change', item)" class="pagin-page">{{ item }}</a>
						<a v-else href="#" @click.prevent="$emit('change', item * -1)" class="pagin-page pagin-dots">...</a>
					</div>
				</div>

				<a href="#" @click.prevent="$emit('change', data.page + 1)" :class="{disabled: data.page >= pages}" class="pagin-arr arr-next">Туда</a>
			</div>
		</div>
</template>

<script>
	export default {
		name: "pagination",
		props: {
			data: {
				type: Object,
				default: () => {
					return {
						total: 0,
						limit: 1,
						page: 1
					}
				}
			}
		},
		computed: {
			pages () {
				return Math.ceil(this.data.total / this.data.limit)
			},
			items ()
			{
				let end = false;
				let arr = [];

				for (let i = 1; i <= this.pages; i++)
				{
					if ((this.data.page <= i + 2 && this.data.page >= i - 2) || i === 1 || i === this.pages || this.pages <= 6)
					{
						end = false;
						arr.push(i);
					}
					else
					{
						if (end === false)
							arr.push(0);

						end = true;
					}
				}

				return arr.map((item, i, arr) =>
				{
					if (item === 0)
						return (arr[i-1] + Math.floor((arr[i+1] - arr[i-1]) / 2)) * -1;

					return item
				})
			}
		}
	}
</script>