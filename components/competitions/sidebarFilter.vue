<template>
	<div>
		<div v-for="item in items" class="results-sidebar-section">
			<h4>{{ item['title'] }}</h4>
			<div class="sidebar-section-list" :class="{ 'double-col': item['code']==='SEASON' }">
				<div v-for="value in item['values']" class="checkbox" :class="{disabled: value['disabled']}">
					<input type="checkbox" :id="value['id']" :name="value['id']" v-model="value['checked']" @change.prevent="send" :disabled="value['disabled']">
					<label :for="value['id']" v-html="value['title']"></label>
				</div>
			</div>
		</div>

		<div class="results-sidebar-section">
			<a href="#" @click.prevent="clear" class="sidebar-filter-clear" :class="{'disabled': checked === 0}">Сбросить фильтр</a>
		</div>
	</div>
</template>

<script>
	export default {
		name: "sidebarFilter",
		props: {
			items: {
				type: Array
			}
		},
		computed: {
			checked ()
			{
				let count = 0;

				if (!this.items)
					return count;

				this.items.forEach((item) =>
				{
					item['values'].forEach((value) =>
					{
						if (value['checked'])
							count++;
					})
				});

				return count
			}
		},
		methods: {
			clear ()
			{
				this.items.forEach((item) =>
				{
					item['values'].forEach((value) => {
						value['checked'] = false
					})
				});

				this.$nextTick(() => {
					this.send()
				})
			},
			send ()
			{
				this.$emit('update');
			}
		},
	}
</script>