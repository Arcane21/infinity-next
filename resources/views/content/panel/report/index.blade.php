@extends('layouts.main.panel')

@section('title', trans('panel.title.reports'))

@section('body')
<section class="reports">
    @if (count($reportedPosts))
    <ul class="reported-posts">
        @foreach($reportedPosts as $reportedPost)
        <li class="reported-post">
            <article class="reported-content">
                <ul class="report-actions actions-post" data-no-instant>
                    <li class="report-action">
                        <a class="report-action"
                            href="{{ route('panel.reports.dismiss.post', ['post' => $reportedPost]) }}">
                            @lang('panel.reports.dismiss_post')
                        </a>
                    </li>
                    @if ($reportedPost->countReportsCanPromote($user) > 0)
                    <li class="report-action">
                        <a class="report-action"
                            href="{{ route('panel.reports.promote.post', ['post' => $reportedPost]) }}">
                            @lang('panel.reports.promote_post')
                        </a>
                    </li>
                    @endif
                    @if ($reportedPost->countReportsCanDemote($user) > 0)
                    <li class="report-action">
                        <a class="report-action"
                            href="{{ route('panel.reports.demote.post', ['post' => $reportedPost]) }}">
                            @lang('panel.reports.demote_post')
                        </a>
                    </li>
                    @endif
                </ul>

                @include( 'content.board.post', [
                    'board'   => $reportedPost->board,
                    'post'    => $reportedPost,
                    'reports' => $reportedPost->reports,
                    'catalog' => false,
                    'preview' => false,
                    'crown'   => true,
                ])
            </article>

            <div class="post-reports">
                @foreach ($reportedPost->reports as $report)
                    @include('content.panel.report.item')
                @endforeach
            </div>

        </li>
        @endforeach
    </ul>
    @else
        <p>@lang('panel.reports.empty')
    @endif
</section>
@stop
