<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker: */

/**
 * Code Data to support Lint for MARC records
 *
 * This module is adapted from the MARC::Lint::CodeData CPAN module for Perl,
 * maintained by Bryan Baldus <eijabb@cpan.org> and available for download at
 * http://search.cpan.org/~eijabb/
 *
 * Current MARC::Lint::CodeData version used as basis for this module: 1.37
 *
 * PHP version 5
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  File_Formats
 * @package   File_MARC
 * @author    Demian Katz <demian.katz@villanova.edu>
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2003-2019 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id: Record.php 308146 2011-02-08 20:36:20Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Lint
/**
 * Contains codes from the MARC code lists for Geographic Areas, Languages, and
 * Countries.
 *
 * Code data is used for validating fields 008, 040, 041, and 043.
 *
 * Also, sources for subfield 2 in 600-651 and 655.
 *
 * Note: According to the official MARC documentation, Sears is not a valid 655
 * term. The code data below treats it as valid, in anticipation of a change in
 * the official documentation.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Lint_CodeData
{

    // {{{ properties
    /**
     * Valid Geographic Area Codes
     * @var array
     */
    public $geogAreaCodes;

    /**
     * Obsolete Geographic Area Codes
     * @var array
     */
    public $obsoleteGeogAreaCodes;

    /**
     * Valid Language Codes
     * @var array
     */
    public $languageCodes;

    /**
     * Obsolete Language Codes
     * @var array
     */
    public $obsoleteLanguageCodes;

    /**
     * Valid Country Codes
     * @var array
     */
    public $countryCodes;

    /**
     * Obsolete Country Codes
     * @var array
     */
    public $obsoleteCountryCodes;

    /**
     * Valid sources for fields 600-651
     * @var array
     */
    public $sources600_651;

    /**
     * Obsolete sources for fields 600-651
     * @var array
     */
    public $obsoleteSources600_651;

    /**
     * Valid sources for field 655
     * @var array
     */
    public $sources655;

    /**
     * Obsolete sources for field 655
     * @var array
     */
    public $obsoleteSources655;

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Start function
     *
     * Initialize code arrays.
     *
     * @return true 
     */
    public function __construct()
    {
        // @codingStandardsIgnoreStart
        // fill the valid Geographic Area Codes array
        $this->geogAreaCodes = explode("\t", "a------	a-af---	a-ai---	a-aj---	a-ba---	a-bg---	a-bn---	a-br---	a-bt---	a-bx---	a-cb---	a-cc---	a-cc-an	a-cc-ch	a-cc-cq	a-cc-fu	a-cc-ha	a-cc-he	a-cc-hh	a-cc-hk	a-cc-ho	a-cc-hp	a-cc-hu	a-cc-im	a-cc-ka	a-cc-kc	a-cc-ki	a-cc-kn	a-cc-kr	a-cc-ku	a-cc-kw	a-cc-lp	a-cc-mh	a-cc-nn	a-cc-pe	a-cc-sh	a-cc-sm	a-cc-sp	a-cc-ss	a-cc-su	a-cc-sz	a-cc-ti	a-cc-tn	a-cc-ts	a-cc-yu	a-ccg--	a-cck--	a-ccp--	a-ccs--	a-ccy--	a-ce---	a-ch---	a-cy---	a-em---	a-gs---	a-ii---	a-io---	a-iq---	a-ir---	a-is---	a-ja---	a-jo---	a-kg---	a-kn---	a-ko---	a-kr---	a-ku---	a-kz---	a-le---	a-ls---	a-mk---	a-mp---	a-my---	a-np---	a-nw---	a-ph---	a-pk---	a-pp---	a-qa---	a-si---	a-su---	a-sy---	a-ta---	a-th---	a-tk---	a-ts---	a-tu---	a-uz---	a-vt---	a-ye---	aa-----	ab-----	ac-----	ae-----	af-----	ag-----	ah-----	ai-----	ak-----	am-----	an-----	ao-----	aopf---	aoxp---	ap-----	ar-----	as-----	at-----	au-----	aw-----	awba---	awgz---	ay-----	az-----	b------	c------	cc-----	cl-----	d------	dd-----	e------	e-aa---	e-an---	e-au---	e-be---	e-bn---	e-bu---	e-bw---	e-ci---	e-cs---	e-dk---	e-er---	e-fi---	e-fr---	e-ge---	e-gi---	e-gr---	e-gw---	e-gx---	e-hu---	e-ic---	e-ie---	e-it---	e-kv---	e-lh---	e-li---	e-lu---	e-lv---	e-mc---	e-mm---	e-mo---	e-mv---	e-ne---	e-no---	e-pl---	e-po---	e-rb---	e-rm---	e-ru---	e-sm---	e-sp---	e-sw---	e-sz---	e-uk---	e-uk-en	e-uk-ni	e-uk-st	e-uk-ui	e-uk-wl	e-un---	e-ur---	e-urc--	e-ure--	e-urf--	e-urk--	e-urn--	e-urp--	e-urr--	e-urs--	e-uru--	e-urw--	e-vc---	e-xn---	e-xo---	e-xr---	e-xv---	e-yu---	ea-----	eb-----	ec-----	ed-----	ee-----	el-----	en-----	eo-----	ep-----	er-----	es-----	ev-----	ew-----	f------	f-ae---	f-ao---	f-bd---	f-bs---	f-cd---	f-cf---	f-cg---	f-cm---	f-cx---	f-dm---	f-ea---	f-eg---	f-et---	f-ft---	f-gh---	f-gm---	f-go---	f-gv---	f-iv---	f-ke---	f-lb---	f-lo---	f-ly---	f-mg---	f-ml---	f-mr---	f-mu---	f-mw---	f-mz---	f-ng---	f-nr---	f-pg---	f-rh---	f-rw---	f-sa---	f-sd---	f-sf---	f-sg---	f-sh---	f-sj---	f-sl---	f-so---	f-sq---	f-ss---	f-sx---	f-tg---	f-ti---	f-tz---	f-ua---	f-ug---	f-uv---	f-za---	fa-----	fb-----	fc-----	fd-----	fe-----	ff-----	fg-----	fh-----	fi-----	fl-----	fn-----	fq-----	fr-----	fs-----	fu-----	fv-----	fw-----	fz-----	h------	i------	i-bi---	i-cq---	i-fs---	i-hm---	i-mf---	i-my---	i-re---	i-se---	i-xa---	i-xb---	i-xc---	i-xo---	l------	ln-----	lnaz---	lnbm---	lnca---	lncv---	lnfa---	lnjn---	lnma---	lnsb---	ls-----	lsai---	lsbv---	lsfk---	lstd---	lsxj---	lsxs---	m------	ma-----	mb-----	me-----	mm-----	mr-----	n------	n-cn---	n-cn-ab	n-cn-bc	n-cn-mb	n-cn-nf	n-cn-nk	n-cn-ns	n-cn-nt	n-cn-nu	n-cn-on	n-cn-pi	n-cn-qu	n-cn-sn	n-cn-yk	n-cnh--	n-cnm--	n-cnp--	n-gl---	n-mx---	n-us---	n-us-ak	n-us-al	n-us-ar	n-us-az	n-us-ca	n-us-co	n-us-ct	n-us-dc	n-us-de	n-us-fl	n-us-ga	n-us-hi	n-us-ia	n-us-id	n-us-il	n-us-in	n-us-ks	n-us-ky	n-us-la	n-us-ma	n-us-md	n-us-me	n-us-mi	n-us-mn	n-us-mo	n-us-ms	n-us-mt	n-us-nb	n-us-nc	n-us-nd	n-us-nh	n-us-nj	n-us-nm	n-us-nv	n-us-ny	n-us-oh	n-us-ok	n-us-or	n-us-pa	n-us-ri	n-us-sc	n-us-sd	n-us-tn	n-us-tx	n-us-ut	n-us-va	n-us-vt	n-us-wa	n-us-wi	n-us-wv	n-us-wy	n-usa--	n-usc--	n-use--	n-usl--	n-usm--	n-usn--	n-uso--	n-usp--	n-usr--	n-uss--	n-ust--	n-usu--	n-xl---	nc-----	ncbh---	nccr---	nccz---	nces---	ncgt---	ncho---	ncnq---	ncpn---	nl-----	nm-----	np-----	nr-----	nw-----	nwaq---	nwaw---	nwbb---	nwbf---	nwbn---	nwcj---	nwco---	nwcu---	nwdq---	nwdr---	nweu---	nwgd---	nwgp---	nwhi---	nwht---	nwjm---	nwla---	nwli---	nwmj---	nwmq---	nwna---	nwpr---	nwsc---	nwsd---	nwsn---	nwst---	nwsv---	nwtc---	nwtr---	nwuc---	nwvb---	nwvi---	nwwi---	nwxa---	nwxi---	nwxk---	nwxm---	p------	pn-----	po-----	poas---	pobp---	poci---	pocw---	poea---	pofj---	pofp---	pogg---	pogu---	poji---	pokb---	poki---	poln---	pome---	pomi---	ponl---	ponn---	ponu---	popc---	popl---	pops---	posh---	potl---	poto---	pott---	potv---	poup---	powf---	powk---	pows---	poxd---	poxe---	poxf---	poxh---	ps-----	q------	r------	s------	s-ag---	s-bl---	s-bo---	s-ck---	s-cl---	s-ec---	s-fg---	s-gy---	s-pe---	s-py---	s-sr---	s-uy---	s-ve---	sa-----	sn-----	sp-----	t------	u------	u-ac---	u-at---	u-at-ac	u-at-ne	u-at-no	u-at-qn	u-at-sa	u-at-tm	u-at-vi	u-at-we	u-atc--	u-ate--	u-atn--	u-cs---	u-nz---	w------	x------	xa-----	xb-----	xc-----	xd-----	zd-----	zju----	zma----	zme----	zmo----	zne----	zo-----	zpl----	zs-----	zsa----	zsu----	zur----	zve----");
        
        // fill the obsolete Geographic Area Codes array
        $this->obsoleteGeogAreaCodes = explode("\t", "t-ay---	e-ur-ai	e-ur-aj	nwbc---	e-ur-bw	f-by---	pocp---	e-url--	cr-----	v------	e-ur-er	et-----	e-ur-gs	pogn---	nwga---	nwgs---	a-hk---	ei-----	f-if---	awiy---	awiw---	awiu---	e-ur-kz	e-ur-kg	e-ur-lv	e-ur-li	a-mh---	cm-----	e-ur-mv	n-usw--	a-ok---	a-pt---	e-ur-ru	pory---	nwsb---	posc---	a-sk---	posn---	e-uro--	e-ur-ta	e-ur-tk	e-ur-un	e-ur-uz	a-vn---	a-vs---	nwvr---	e-urv--	a-ys---");
        
        // fill the valid Language Codes array
        $this->languageCodes = explode("\t", "   	aar	abk	ace	ach	ada	ady	afa	afh	afr	ain	aka	akk	alb	ale	alg	alt	amh	ang	anp	apa	ara	arc	arg	arm	arn	arp	art	arw	asm	ast	ath	aus	ava	ave	awa	aym	aze	bad	bai	bak	bal	bam	ban	baq	bas	bat	bej	bel	bem	ben	ber	bho	bih	bik	bin	bis	bla	bnt	bos	bra	bre	btk	bua	bug	bul	bur	byn	cad	cai	car	cat	cau	ceb	cel	cha	chb	che	chg	chi	chk	chm	chn	cho	chp	chr	chu	chv	chy	cmc	cop	cor	cos	cpe	cpf	cpp	cre	crh	crp	csb	cus	cze	dak	dan	dar	day	del	den	dgr	din	div	doi	dra	dsb	dua	dum	dut	dyu	dzo	efi	egy	eka	elx	eng	enm	epo	est	ewe	ewo	fan	fao	fat	fij	fil	fin	fiu	fon	fre	frm	fro	frr	frs	fry	ful	fur	gaa	gay	gba	gem	geo	ger	gez	gil	gla	gle	glg	glv	gmh	goh	gon	gor	got	grb	grc	gre	grn	gsw	guj	gwi	hai	hat	hau	haw	heb	her	hil	him	hin	hit	hmn	hmo	hrv	hsb	hun	hup	iba	ibo	ice	ido	iii	ijo	iku	ile	ilo	ina	inc	ind	ine	inh	ipk	ira	iro	ita	jav	jbo	jpn	jpr	jrb	kaa	kab	kac	kal	kam	kan	kar	kas	kau	kaw	kaz	kbd	kha	khi	khm	kho	kik	kin	kir	kmb	kok	kom	kon	kor	kos	kpe	krc	krl	kro	kru	kua	kum	kur	kut	lad	lah	lam	lao	lat	lav	lez	lim	lin	lit	lol	loz	ltz	lua	lub	lug	lui	lun	luo	lus	mac	mad	mag	mah	mai	mak	mal	man	mao	map	mar	mas	may	mdf	mdr	men	mga	mic	min	mis	mkh	mlg	mlt	mnc	mni	mno	moh	mon	mos	mul	mun	mus	mwl	mwr	myn	myv	nah	nai	nap	nau	nav	nbl	nde	ndo	nds	nep	new	nia	nic	niu	nno	nob	nog	non	nor	nqo	nso	nub	nwc	nya	nym	nyn	nyo	nzi	oci	oji	ori	orm	osa	oss	ota	oto	paa	pag	pal	pam	pan	pap	pau	peo	per	phi	phn	pli	pol	pon	por	pra	pro	pus	que	raj	rap	rar	roa	roh	rom	rum	run	rup	rus	sad	sag	sah	sai	sal	sam	san	sas	sat	scn	sco	sel	sem	sga	sgn	shn	sid	sin	sio	sit	sla	slo	slv	sma	sme	smi	smj	smn	smo	sms	sna	snd	snk	sog	som	son	sot	spa	srd	srn	srp	srr	ssa	ssw	suk	sun	sus	sux	swa	swe	syc	syr	tah	tai	tam	tat	tel	tem	ter	tet	tgk	tgl	tha	tib	tig	tir	tiv	tkl	tlh	tli	tmh	tog	ton	tpi	tsi	tsn	tso	tuk	tum	tup	tur	tut	tvl	twi	tyv	udm	uga	uig	ukr	umb	und	urd	uzb	vai	ven	vie	vol	vot	wak	wal	war	was	wel	wen	wln	wol	xal	xho	yao	yap	yid	yor	ypk	zap	zbl	zen	zha	znd	zul	zun	zxx	zza");
        
        // fill the obsolete Language Codes array
        $this->obsoleteLanguageCodes = explode("\t", "ajm	esk	esp	eth	far	fri	gag	gua	int	iri	cam	kus	mla	max	mol	lan	gal	lap	sao	gae	scc	scr	sho	snh	sso	swz	tag	taj	tar	tru	tsw");
        
        // fill the valid Country Codes array
        $this->countryCodes = explode("\t", "aa 	abc	aca	ae 	af 	ag 	ai 	aj 	aku	alu	am 	an 	ao 	aq 	aru	as 	at 	au 	aw 	ay 	azu	ba 	bb 	bcc	bd 	be 	bf 	bg 	bh 	bi 	bl 	bm 	bn 	bo 	bp 	br 	bs 	bt 	bu 	bv 	bw 	bx 	ca 	cau	cb 	cc 	cd 	ce 	cf 	cg 	ch 	ci 	cj 	ck 	cl 	cm 	co 	cou	cq 	cr 	ctu	cu 	cv 	cw 	cx 	cy 	dcu	deu	dk 	dm 	dq 	dr 	ea 	ec 	eg 	em 	enk	er 	es 	et 	fa 	fg 	fi 	fj 	fk 	flu	fm 	fp 	fr 	fs 	ft 	gau	gb 	gd 	gh 	gi 	gl 	gm 	go 	gp 	gr 	gs 	gt 	gu 	gv 	gw 	gy 	gz 	hiu	hm 	ho 	ht 	hu 	iau	ic 	idu	ie 	ii 	ilu	inu	io 	iq 	ir 	is 	it 	iv 	iy 	ja 	ji 	jm 	jo 	ke 	kg 	kn 	ko 	ksu	ku 	kv 	kyu	kz 	lau	lb 	le 	lh 	li 	lo 	ls 	lu 	lv 	ly 	mau	mbc	mc 	mdu	meu	mf 	mg 	miu	mj 	mk 	ml 	mm 	mnu	mo 	mou	mp 	mq 	mr 	msu	mtu	mu 	mv 	mw 	mx 	my 	mz 	na 	nbu	ncu	ndu	ne 	nfc	ng 	nhu	nik	nju	nkc	nl 	nmu	nn 	no 	np 	nq 	nr 	nsc	ntc	nu 	nuc	nvu	nw 	nx 	nyu	nz 	ohu	oku	onc	oru	ot 	pau	pc 	pe 	pf 	pg 	ph 	pic	pk 	pl 	pn 	po 	pp 	pr 	pw 	py 	qa 	qea	quc	rb 	re 	rh 	riu	rm 	ru 	rw 	sa 	sc 	scu	sd 	sdu	se 	sf 	sg 	sh 	si 	sj 	sl 	sm 	sn 	snc	so 	sp 	sq 	sr 	ss 	st 	stk	su 	sw 	sx 	sy 	sz 	ta 	tc 	tg 	th 	ti 	tk 	tl 	tma	tnu	to 	tr 	ts 	tu 	tv 	txu	tz 	ua 	uc 	ug 	uik	un 	up 	utu	uv 	uy 	uz 	vau	vb 	vc 	ve 	vi 	vm 	vp 	vra	vtu	wau	wea	wf 	wiu	wj 	wk 	wlk	ws 	wvu	wyu	xa 	xb 	xc 	xd 	xe 	xf 	xga	xh 	xj 	xk 	xl 	xm 	xn 	xna	xo 	xoa	xp 	xr 	xra	xs 	xv 	xx 	xxc	xxk	xxu	ye 	ykc	za ");
        
        // fill the obsolete Country Codes array
        $this->obsoleteCountryCodes = explode("\t", "ai 	air	ac 	ajr	bwr	cn 	cz 	cp 	ln 	cs 	err	gsr	ge 	gn 	hk 	iw 	iu 	jn 	kzr	kgr	lvr	lir	mh 	mvr	nm 	pt 	rur	ry 	xi 	sk 	xxr	sb 	sv 	tar	tt 	tkr	unr	uk 	ui 	us 	uzr	vn 	vs 	wb 	ys 	yu ");
        
        // the codes cash, lcsh, lcshac, mesh, nal, and rvm are covered by 2nd
        // indicators in 600-655
        // they are only used when indicators are not available
        $this->sources600_651 = explode("\t", "aass	aat	abne	aedoml	afo	afset	agrifors	agrovoc	agrovocf	agrovocs	aiatsisl	aiatsisp	aiatsiss	aktp	albt	allars	apaist	armac	asft	ashlnl	asrcrfcd	asrcseo	asrctoa	asth	ated	atg	atla	aucsh	bare	barn	bhb	bella	bet	bhammf	bhashe	bib1814	bibalex	biccbmc	bicssc	bidex	bisacsh	bisacmt	bisacrt	bjornson	blcpss	blmlsh	blnpn	bokbas	bt	btr	cabt	cash	cbk	cck	ccsa	cct	ccte	cctf	cdcng	ceeus	chirosh	cht	ciesiniv	cilla	collett	conorsi	csahssa	csalsct	csapa	csh	csht	cstud	czenas	czmesh	dacs	dbn	dcs	ddcri	ddcrit	ddcut	dissao	dit	dltlt	dltt	drama	dtict	ebfem	eclas	eet	eflch	eks	embiaecid	embne	emnmus	ept	erfemn	ericd	est	eum	eurovocen	eurovocfr	eurovocsl	fast	fes	finmesh	fire	fmesh	fnhl	francis	fssh	galestne	gccst	gcipmedia	gcipplatform	gem	gemet	georeft	gnd	gnis	gst	gtt	hamsun	hapi	hkcan	helecon	henn	hlasstg	hoidokki	hrvmesh	huc	humord	iaat	ibsen	ica	icpsr	idas	idsbb	idszbz	idszbzes	idszbzna	idszbzzg	idszbzzh	idszbzzk	iescs	iest	ilot	ilpt	inist	inspect	ipat	ipsp	isis	itglit	itoamc	itrt	jhpb	jhpk	jlabsh	juho	jupo	jurivoc	kaa	kaba	kao	kassu	kauno	kaunokki	kdm	khib	kito	kitu	kkts	koko	kssbar	kta	kto	ktpt	ktta	kubikat	kula	kulo	kupu	lacnaf	lapponica	larpcal	lcac	lcdgt	lcmpt	lcsh	lcshac	lcstt	lctgm	lemac	lemb	liito	liv	lnmmbr	local	ltcsh	lua	maaq	maotao	mar	masa	mech	mero	mesh	mipfesd	mmm	mpirdes	msc	msh	mtirdes	musa	muso	muzeukc	muzeukn	muzvukci	naf	nal	nalnaf	nasat	nbdbt	nbiemnfag	ncjt	ndlsh	netc	ndllsh	nicem	nimacsc	nlgaf	nlgkk	nlgsh	nlmnaf	no-ubo-mr	noraf	noram	norbok	normesh	noubomn	noubojur	nsbncf	nskps	ntcpsc	ntcsd	ntids	ntissc	nzggn	nznb	odlt	ogst	onet	opms	ordnok	pascal	pepp	peri	pha	pkk	pleiades	pmbok	pmcsg	pmont	pmt	poliscit	popinte	precis	prvt	psychit	puho	quiding	qlsp	qrma	qrmak	qtglit	raam	ram	rasuqam	renib	reo	rero	rerovoc	rma	rpe	rswk	rswkaf	rugeo	rurkp	rvm	rvmgd	samisk	sanb	sao	sbiao	sbt	scbi	scgdst	scisshl	scot	sears	sfit	sgc	sgce	shbe	she	shsples	sigle	sipri	sk	skon	slem	smda	snt	socio	solstad	sosa	spines	ssg	sthus	stw	swd	swemesh	taika	tasmas	taxhs	tbit	tbjvp	tekord	tero	tesa	tesbhaecid	test	tgn	tha	thema	thesoz	thia	tho	thub	tips	tisa	tlka	tlsh	toit	trfarn	trfbmb	trfgr	trfoba	trfzb	trt	trtsa	tsht	tsr	ttka	ttll	tucua	udc	ukslc	ulan	umitrist	unbisn	unbist	unescot	usaidt	valo	vcaadu	vffyl	vmj	waqaf	watrest	wgst	wot	wpicsh	ysa	yso");
        $this->obsoleteSources600_651 = explode("\t", "cash	lcsh	lcshac	mesh	nal	nobomn	noubojor	reroa	rvm");
        $this->sources655 = explode("\t", "aat	afset	aiatsisl	aiatsisp	aiatsiss	aktp	alett	amg	asrcrfcd	asrcseo	asrctoa	asth	aucsh	barn	barngf	bib1814	bibalex	biccbmc	bidex	bgtchm	bisacsh	bisacmt	bisacrt	bjornson	bt	cash	chirosh	cct	cdcng	cjh	collett	conorsi	csht	czenas	dacs	dcs	dct	ddcut	eet	eflch	embne	emnmus	ept	erfemn	ericd	estc	eurovocen	eurovocsl	fast	fbg	fgtpcm	finmesh	fire	ftamc	galestne	gatbeg	gem	gmd	gmgpc	gnd	gsafd	gst	gtlm	hamsun	hapi	hkcan	hoidokki	ica	ilot	isbdcontent	isbdmedia	itglit	itrt	jhpb	jhpk	kkts	lacnaf	lcgft	lcmpt	lcsh	lcshac	lcstt	lctgm	lemac	lobt	local	maaq	mar	marccategory	marcform	marcgt	marcsmd	mech	mesh	migfg	mim	msh	muzeukc	muzeukn	muzeukv	muzvukci	nal	nalnaf	nbdbgf	nbiemnfag	ndlsh	netc	ngl	nimafc	nlgaf	nlgkk	nlgsh	nlmnaf	nmc	no-ubo-mr	noraf	noram	nsbncf	ntids	nzcoh	nzggn	nznb	onet	opms	ordnok	pkk	pmcsg	pmt	proysen	quiding	qlsp	qrmak	qtglit	raam	radfg	rasuqam	rbbin	rbgenr	rbmscv	rbpap	rbpri	rbprov	rbpub	rbtyp	rdabf	rdabs	rdacarrier	rdaco	rdacontent	rdacpc	rdact	rdafnm	rdafs	rdaft	rdagen	rdagrp	rdagw	rdalay	rdamat	rdamedia	rdamt	rdapf	rdapm	rdapo	rdarm	rdarr	rdaspc	rdatc	rdatr	rdavf	reo	rerovoc	reveal	rma	rswk	rswkaf	rugeo	rvm	rvmgf	sao	saogf	scbi	sears	sgc	sgce	sgp	sipri	skon	snt	socio	spines	ssg	stw	swd	swemesh	tbit	thema	tesa	tgfbne	thesoz	tho	thub	toit	tsht	tucua	ukslc	ulan	vgmsgg	vgmsng	vmj	waqaf");
        $this->obsoleteSources655 = explode("\t", "cash	ftamc	lcsh	lcshac	marccarrier	marccontent	marcmedia	mesh	nal	reroa	rvm");
        // @codingStandardsIgnoreEnd
    }
    // }}}
}
// }}}
