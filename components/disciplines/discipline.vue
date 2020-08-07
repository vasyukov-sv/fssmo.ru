<template>
	<div class="discipline-panel" :class="{'no-img': !item['picture']}">
		<div class="discipline-panel-img" v-if="item['picture']" :style="{backgroundImage: 'url('+item['picture']['image']+')'}">
			<img :src="item['picture']['image']" v-if="item['picture']" :alt="item['picture']['text_1']" />
			<div v-if="item['picture']['text_1'].length || item['picture']['text_2'].length" class="discipline-img-caption">
				<div v-if="item['picture']['text_1'].length" class="discipline-img-title">{{ item['picture']['text_1'] }}</div>
				{{ item['picture']['text_2'] }}
			</div>
		</div>
		<div class="discipline-panel-content-wrap">
			<div class="discipline-panel-content">
				<div class="discipline-content">
					<div class="discipline-panel-info" v-if="item['members'] || item['targets']">
						<div class="discipline-members" v-html="item['members']"></div>
						<div class="discipline-targets" v-html="item['targets']"></div>
					</div>
					<div class="discipline-title">{{ item['title'] }}</div>
					<div class="discipline-text" v-if="item['text']" v-html="item['text']"></div>
				</div>
				<div class="discipline-doc-container" v-if="item['rules']">
					<a :href="item['rules']['src']" target="_blank" class="discipline-doc-link">{{ item['rules']['title'] }} ({{ item['rules']['extension'] | uppercase }}, {{ size }})</a>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: "discipline",
		props: {
			item: {
				type: Object,
			}
		},
		computed: {
			size () {

				if (!this.item['rules'])
					return '';

				return (this.item['rules']['size'] / (1024 * 1024)).toFixed(2)+' Mb'
			}
		},
	}
</script>