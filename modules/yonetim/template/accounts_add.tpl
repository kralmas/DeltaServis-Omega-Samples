<!-- UTF8, 27.02.2019 -->
<div class="tabbable-custom nav-justified">
	<ul class="nav nav-tabs nav-justified">
		<li class="active"><a href="#tab_0" data-toggle="tab" aria-expanded="true"> Giriş Bilgileri </a></li>
		<li><a href="#tab_1" data-toggle="tab" aria-expanded="true"> Yetkili Bilgileri </a></li>
		<li><a href="#tab_2" data-toggle="tab" aria-expanded="true"> Müşteri Bilgileri </a></li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane active" id="tab_0">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Kullanıcı Adı (*)</label><br>
					{acc_name}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Parola</label><br>
					{acc_pass}
				</td>
			</tr>
			<tr class="ds_tr ds_tr_2">
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Adı, Soyadı (*)</label><br>
					{name_surname}
				</td>
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Fotoğraf</label><br>
					{imgurl}
				</td>
			</tr>
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Durum (*)</label><br>
					{status}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab_1">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Departman (*)</label><br>
					{dep_id}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Yetki (*)</label><br>
					{user_level}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab_2">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Müşteri</label><br>
					{cus_id}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Müşteri Departmanı</label><br>
					{cus_dep_id}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
	</div>
</div>