<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
		<section class="container">
			<div class="fssmo-join-wrap">
				<div class="join-left">
					<div class="text-editable">
						<h4>Какие возможности получают члены ФССМО</h4>
						<ul>
							<li>Быстро оформить спортивный разряд или звание мастера спорта и получить зачетную классификационную книжку спортсмена и разрядный знак по результатам соревнований.</li>
							<li>Участвовать во всероссийских соревнованиях по спортингу и спортинг-компакту от имени ФССМО.</li>
							<li>Представлять Московскую область на всероссийских соревнованиях по классическим видам стендовой стрельбы, если вы показываете высокие результаты на отборочных соревнованиях области.</li>
							<li>Получать помощь в оформлении документов на ввоз или вывоз оружия за пределы РФ для участия в международных соревнованиях по стендовой стрельбе и организации учебно-тренировочного процесса.</li>
						</ul>
						<br>
						<h4>Как вступить</h4>
						<ol>
							<li>Заполнить заявление и анкету на сайте или распечатать бумажные варианты — в этом случае их нужно отправить на почту <a href="mailto:fssmo@mail.ru">fssmo@mail.ru</a> или передать в оргкомитет на любом соревновании ФССМО.</li>
							<li>Оплатить вступительный взнос 2000 рублей. В него входит комплект атрибутики. </li>
							<li>Если вы планируете оформлять зачетную классификационную книжку спортсмена, нужно принести фотографию 3x4 и копию паспорта.</li>
						</ol>
					</div>
					<div class="join-form-wrap">

						<nav class="page-list-nav">
							<ul>
								<li><a href="#" :class="{ 'active' : formActive }" @click.prevent="formActive = true">Заполнить онлайн</a></li>
								<li><a href="#" :class="{ 'active' : !formActive }" @click.prevent="formActive = false">Скачать заявление и анкету</a></li>
							</ul>
						</nav>

						<div  v-if="formActive">
							<transition name="fade">
								<form method="post" class="join-form" @submit.prevent="send">
									<div class="form-section">
										<h3>Заявление</h3>
										<div class="page-default-form">

											<div class="form-group form-group-label-inline">
												<label class="form-label">ФИО</label>
												<div class="input-container">
													<input type="text" name="name" v-model="form.name" :class="{error: $v.form.name.$error}" @change="$v.form.name.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Паспорт</label>
												<div class="input-container">
													<input type="text" name="passport" autocomplete="off" v-model="form.passport" :class="{error: $v.form.passport.$error}" @change="$v.form.passport.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Кем выдан</label>
												<div class="input-container">
													<textarea name="passport_issue_place" autocomplete="off" v-model="form.passport_issue_place" :class="{error: $v.form.passport_issue_place.$error}" @change="$v.form.passport_issue_place.$touch()"></textarea>
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Дата выдачи</label>
												<div class="input-container">
													<input ref="issue_date" type="text" name="passport_issue_date" autocomplete="off" v-model="form.passport_issue_date" :class="{error: $v.form.passport_issue_date.$error}" @change="$v.form.passport_issue_date.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Адрес регистрации</label>
												<div class="input-container">
													<textarea name="registration_address" v-model="form.registration_address" :class="{error: $v.form.registration_address.$error}" @change="$v.form.registration_address.$touch()"></textarea>
												</div>
											</div>

										</div>

									</div>

									<div class="form-section">
										<h3>Анкета</h3>

										<div class="page-default-form">

											<div class="form-group form-group-label-inline">
												<label class="form-label">Дата рождения</label>
												<div class="input-container">
													<input ref="birthday" type="text" name="birthday" v-model="form.birthday" :class="{error: $v.form.birthday.$error}" @change="$v.form.birthday.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Спортивное звание</label>
												<div class="input-container">
													<div class="button-radio-group">
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="МС" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">МС</div>
														</label>
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="КМС" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">КМС</div>
														</label>
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="I" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">I</div>
														</label>
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="II" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">II</div>
														</label>
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="III" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">III</div>
														</label>
														<label class="radio-group-item">
															<input type="radio" name="sport_title" v-model="form.sport_title" value="" :class="{error: $v.form.sport_title.$error}" @change="$v.form.sport_title.$touch()">
															<div class="group-radio-content">НЕТ</div>
														</label>
													</div>
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Год получения</label>
												<div class="input-container">
													<input type="text" name="sport_title_date" v-model="form.sport_title_date" :class="{error: $v.form.sport_title_date.$error}" @change="$v.form.sport_title_date.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">ФИО Первого тренера</label>
												<div class="input-container">
													<input type="text" name="first_trainer_name" v-model="form.first_trainer_name" :class="{error: $v.form.first_trainer_name.$error}" @change="$v.form.first_trainer_name.$touch()">
												</div>
											</div>

											<div class="form-group form-group-label-inline">
												<label class="form-label">Фио тренера в настоящее время</label>
												<div class="input-container">
													<input type="text" name="current_trainer_name" v-model="form.current_trainer_name" :class="{error: $v.form.current_trainer_name.$error}" @change="$v.form.current_trainer_name.$touch()">
												</div>
											</div>

										</div>
										<br>

										<h5>Контактная информация</h5>
										<div class="page-default-form">

											<div class="form-group form-group-label-inline">
												<label class="form-label">Телефон</label>
												<div class="input-container">
													<InputPhone name="phone" v-model="form.phone" :class="{error: $v.form.phone.$error}" @change="$v.form.phone.$touch()"/>
												</div>
											</div>
											<div class="form-group form-group-label-inline">
												<label class="form-label">e-mail</label>
												<div class="input-container">
													<input type="text" name="email" v-model="form.email" :class="{error: $v.form.email.$error}" @change="$v.form.email.$touch()">
												</div>
											</div>
										</div>
										<br>
										<h5>Стоимость вступления в клуб - {{ info['price']['value'] }} ₽</h5>
										<div class="page-default-form">
											<div class="form-group">
												<div class="radio">
													<input type="radio" v-model="payment" id="payment_online" name="payment" value="online" />
													<label for="payment_online">Оплата онлайн</label>
												</div>
											</div>
											<div v-if="user !== null && user['budget'] && user['budget']['value'] > 0" class="form-group">
												<div class="radio">
													<input type="radio" v-model="payment" id="payment_balance" name="payment" value="balance" />
													<label v-if="user['budget']['value'] < info['price']['value']" for="payment_balance">
														Оплата онлайн + с баланса <b>(ваш баланс {{ user['budget']['value'] }}  ₽)</b>
													</label>
													<label v-else for="payment_balance">
														Оплата с баланса <b>(ваш баланс {{ user['budget']['value'] }}  ₽)</b>
													</label>
												</div>
											</div>
											<div class="form-group">
												<div class="radio">
													<input type="radio" v-model="payment" id="payment_offline" name="payment" value="offline" />
													<label for="payment_offline">Оплата наличными</label>
												</div>
											</div>
											<div class="form-group form-group-label-inline">
												<div class="form-btn">
													<button type="submit" class="btn btn-gold">Оплатить</button>
												</div>
											</div>
										</div>
									</div>
								</form>
							</transition>
						</div>

						<div v-else>
							<transition name="fade">
								<div class="docs-list">
									<div class="doc-item">
										<div class="doc-info">
											<a href="/docs/zayavlenie.pdf" target="_blank"  class="doc-title">Заявление</a>
											<span class="doc-size">(pdf, 111 Кб)</span>
										</div>
										<div class="doc-btn-container">
											<a href="/docs/zayavlenie.pdf" target="_blank" class="doc-btn">Скачать</a>
										</div>
									</div>
									<div class="doc-item">
										<div class="doc-info">
											<a href="/docs/anketa.pdf" target="_blank"  class="doc-title">Анкета</a>
											<span class="doc-size">(pdf, 115 Кб)</span>
										</div>
										<div class="doc-btn-container">
											<a href="/docs/anketa.pdf" target="_blank" class="doc-btn">Скачать</a>
										</div>
									</div>
								</div>
							</transition>
						</div>

					</div>

				</div>
				<div class="join-right">
					<div class="blue-sidebar" v-sticky>
						<div class="enter-prices">
							<div class="price-section">
								<div class="price-title">Вступительный взнос {{ info['items']['FSSMO_ENTRANCE_FEE']['value'] }}&nbsp;&#8381;</div>
								<div class="price-text">
									Включает комплект атрибутики.
								</div>
							</div>
							<div class="price-section">
								<div class="price-title">Ежегодный членский взнос {{ info['items']['FSSMO_MEMBERSHIP_FEE']['value'] }}&nbsp;&#8381;</div>
							</div>
							<div class="price-section">
								<div class="price-title">Выбор личного номера 5000&nbsp;&#8381;</div>
								<div class="price-text">
									Деньги идут на организацию соревнований Московской области по скиту и трапу среди юношей и девушек.
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
</template>

<script>
	import InputPhone from '~/components/inputPhone'
	import SuccessModal from '~/components/successModal.vue'
	import gql from 'graphql-tag';
	import { required, email } from 'vuelidate/lib/validators'
	import {paymentHandler} from "~/utils/helpers"

	const informationQuery = gql`query {
		user: currentUser {
			budget
		}
		enterInClub
	}`;

	const formMutation = gql`mutation ($data: Objects!, $payment: String!) {
		enterForm (data: $data, payment: $payment)
	}`;

	export default {
		components: {
			InputPhone,
		},
		async asyncData (context)
		{
			try
			{
				let informationRequest = context.app.$apollo.query({
					query: informationQuery
				})

				let [info] = await Promise.all([
					informationRequest,
					context.store.dispatch('getPageInfo')
				])

				return {
					info: info.data['enterInClub'],
					user: info.data['user'],
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
		data () {
			return {
				formActive: true,
				form: {
					name: '',
					passport: '',
					passport_issue_place: '',
					passport_issue_date: '',
					registration_address: '',

					birthday: '',
					sport_title: '',
					sport_title_date: '',
					first_trainer_name: '',
					current_trainer_name: '',

					phone: '',
					email: '',
				},
				payment: 'online',
			}
		},
		validations: {
			form: {
				name: {
					required
				},
				passport: {
					required
				},
				passport_issue_place: {
					required
				},
				passport_issue_date: {
					required
				},
				registration_address: {
					required
				},
				birthday: {
					required
				},
				sport_title: {
					required
				},
				sport_title_date: {
					required
				},
				first_trainer_name: {
					required
				},
				current_trainer_name: {
					required
				},
				phone: {
					required
				},
				email: {
					required,
					email
				},
			}
		},
		mounted ()
		{
			let Inputmask = require('inputmask')

			Inputmask({
				'mask': '99.99.9999',
				showMaskOnHover: false,
				clearIncomplete: true
			})
			.mask(this.$refs['issue_date']);

			Inputmask({
				'mask': '99.99.9999',
				showMaskOnHover: false,
				clearIncomplete: true
			})
			.mask(this.$refs['birthday']);
		},
		methods: {
			async send ()
			{
				this.$v.$touch()

				if (this.$v.$invalid)
					return

				try
				{
					const result = await this.$apollo.mutate({
						mutation: formMutation,
						variables: {
							data: this.form,
							payment: this.payment,
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['enterForm']
					})

					if (typeof result['order'] !== 'undefined' && result['order'] !== null)
						paymentHandler(result['order'])
					else
					{
						this.$modal.show(SuccessModal, {
							text: 'Заявка отправлена'
						})
					}
				}
				catch (e)
				{
					this.$modal.show(SuccessModal, {
						text: e.message,
						error: true,
					})
				}
			}
		}
	}
</script>